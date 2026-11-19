<?php

namespace App\Services;

use App\Models\Career;
use Illuminate\Support\Collection;

class CareerGuide
{
    public function reply(string $message, array &$state): string
    {
        $text = $this->normalize($message);
        $careers = Career::orderBy('title')->get()->keyBy('id');
        $named = $this->namedCareers($text, $careers);
        $previous = collect($state['last'] ?? [])->map(fn ($id) => $careers->get($id))->filter()->values();
        $positions = $this->positions($text);
        $ordinal = $positions[0] ?? null;
        $stage = $state['stage'] ?? null;

        if (preg_match('/^(start|help me choose|find (my )?(career )?matches|guide me|career quiz)$/', $text)) {
            $state = ['stage' => 'level'];

            return 'What stage are you at? For example, first-year undergraduate, final-year student, or recent graduate.';
        }

        if (preg_match('/^(hi|hello|hey|good (morning|afternoon|evening))$/', $text)) {
            return 'Hi! Tell me what you enjoy studying, ask about a STEM career, or say “help me choose” for guided recommendations.';
        }

        if (preg_match('/^(thanks|thank you|thank you so much|okay thanks)$/', $text)) {
            return 'You’re welcome! You can ask about skills, coursework, or a project whenever you’re ready.';
        }

        $compare = preg_match('/\b(compare|versus|vs|difference|which (one )?should i choose)\b/', $text);
        if ($compare || (($state['awaiting_comparison'] ?? false) && ($named->isNotEmpty() || $positions))) {
            return $this->compare($named, $previous, $positions, $state);
        }

        $recommend = preg_match('/\b(career options|career paths|careers|jobs|recommend|suggest|what can i do with|i (like|love|prefer|enjoy)|interested in)\b/', $text);
        $detail = $this->detail($text);
        if (preg_match('/\b(salary|salaries|pay range|job openings|vacancies|visa|licen[cs]e)\b/', $text)) {
            return 'I can help with career duties, coursework, skills and starter projects. For current pay, vacancies or licensing, check a recent local job listing or the relevant professional body.';
        }

        $directQuestion = $detail || preg_match('/\b(what|how|tell|explain|about)\b/', $text);
        $canDescribe = $named->isNotEmpty() && ($stage === null || $directQuestion);
        if (!$recommend && ($canDescribe || (($detail || $ordinal !== null) && $previous->isNotEmpty()))) {
            if ($named->isEmpty() && $ordinal !== null && !$previous->has($ordinal)) {
                return 'That option was outside the last set of recommendations. Which career did you mean?';
            }

            $focus = $previous->firstWhere('id', $state['focus'] ?? null);
            $selected = match (true) {
                $named->isNotEmpty() => $named,
                $ordinal !== null => collect([$previous[$ordinal]]),
                $focus !== null => collect([$focus]),
                default => $previous,
            };

            if ($named->isNotEmpty() && $named->contains(fn (Career $career) => !$previous->contains('id', $career->id))) {
                $state['last'] = $named->pluck('id')->all();
            }

            $state['focus'] = $selected->count() === 1 ? $selected->first()->id : null;

            return $selected->map(fn (Career $career) => $this->describe($career, $detail))->implode("\n\n");
        }

        if ($stage === 'level') {
            if (!preg_match('/\b(year|level|student|undergraduate|postgraduate|graduate|graduated|secondary|university|college|freshman|fresher|[1-5]00|skip)\b/', $text)) {
                return 'Which stage best describes you? Try first-year student, final-year student, recent graduate, or “skip”.';
            }

            $state['level'] = $text;
            $state['stage'] = 'subjects';

            return 'Which STEM subjects do you enjoy? You can name more than one, such as biology and statistics.';
        }

        $interests = $this->positiveInterests($text);
        $matchedInterest = $careers->contains(fn (Career $career) => $this->matches($interests, $career) !== []);
        if ($stage === 'subjects') {
            if (!$matchedInterest) {
                return 'Could you name a subject or topic you enjoy, such as chemistry, electronics, mathematics, or programming?';
            }

            $state['subjects'] = $interests;
            $state['stage'] = 'interests';

            return 'What kind of work sounds interesting? For example, writing code, doing experiments, designing machines, or working with data. You can also say “skip”.';
        }

        if (!$matchedInterest && $text !== 'skip') {
            if ($stage === 'interests') {
                return 'Which activities do you enjoy? Try analysing data, designing machines, doing experiments, or say “skip”.';
            }

            return 'Tell me a STEM subject or activity you enjoy, or ask about a specific career. For example, “I like physics and designing circuits.”';
        }

        $profile = trim(($stage === 'interests' ? $state['subjects'].' ' : '').($text === 'skip' ? '' : $interests));

        return $this->recommend($profile, $careers, $state);
    }

    private function compare(Collection $named, Collection $previous, array $positions, array &$state): string
    {
        $pending = collect($state['comparison_names'] ?? [])->map(fn ($id) => $previous->firstWhere('id', $id))
            ->filter();
        if ($positions && collect($positions)->contains(fn (int $index) => !$previous->has($index))) {
            return 'One of those option numbers was outside the last set. Name the two careers or use two available option numbers.';
        }

        $choices = $pending->merge($named)->merge(
            collect($positions)->map(fn (int $index) => $previous[$index])
        )->unique('id')->values();

        if ($choices->isEmpty() && $previous->count() === 2) {
            $choices = $previous;
        }

        if ($choices->count() !== 2) {
            $state['awaiting_comparison'] = true;
            $state['comparison_names'] = $choices->count() === 1 ? $choices->pluck('id')->all() : [];
            if ($choices->count() === 1) {
                $state['last'] = $previous->merge($choices)->unique('id')->pluck('id')->all();

                return "Which career should I compare with {$choices->first()->title}?";
            }

            return 'Which two careers would you like to compare? Name both or say “compare options 1 and 2”.';
        }

        unset($state['awaiting_comparison'], $state['comparison_names'], $state['focus']);
        if ($previous->isEmpty() || $choices->contains(fn (Career $career) => !$previous->contains('id', $career->id))) {
            $state['last'] = $choices->pluck('id')->all();
        }

        return $choices->map(fn (Career $career) => "{$career->title}: {$career->description}\n"
            ."What to study: {$career->courses}\nSkills: {$career->skills}\nTry this: {$career->first_step}"
        )->implode("\n\n");
    }

    private function recommend(string $profile, Collection $careers, array &$state): string
    {
        $suggestions = $careers
            ->map(fn (Career $career) => ['career' => $career, 'matches' => $this->matches($profile, $career)])
            ->filter(fn (array $item) => $item['matches'] !== [])
            ->sortByDesc(fn (array $item) => count($item['matches']))
            ->take(3)
            ->values();

        if ($suggestions->isEmpty()) {
            return 'Tell me a STEM subject or activity you enjoy, or ask about a specific career.';
        }

        unset($state['stage'], $state['focus'], $state['awaiting_comparison'], $state['comparison_names']);
        $state['last'] = $suggestions->pluck('career.id')->all();
        $level = $state['level'] ?? '';
        $next = match (true) {
            str_contains($level, 'final') => 'You could explore one of these ideas for your final-year project or portfolio.',
            preg_match('/\b(graduate|graduated)\b/', $level) => 'You could turn one of these projects into a portfolio sample for applications.',
            default => 'Pick one small project to try alongside your studies.',
        };

        return "Based on the interests you shared, here are some paths to explore.\n\n"
            .$suggestions->map(function (array $item, int $index) {
                $career = $item['career'];
                $reason = implode(', ', array_slice($item['matches'], 0, 4));

                return ($index + 1).". {$career->title}\nMatches your interests in {$reason}.\n{$career->description}\nTry this: {$career->first_step}";
            })->implode("\n\n")
            ."\n\n{$next} Ask me to compare two paths or ask about the skills for an option.";
    }

    private function detail(string $text): ?string
    {
        return match (true) {
            (bool) preg_match('/\b(skill|skills|tools|learn)\b/', $text) => 'skills',
            (bool) preg_match('/\b(course|courses|degree|qualification|qualifications|what should i study)\b/', $text) => 'courses',
            (bool) preg_match('/\b(project|projects|start|begin|portfolio|first step|next step|get into)\b/', $text) => 'first_step',
            (bool) preg_match('/\b(role|responsibilities|tell me more|what does|what do they do|day to day)\b/', $text) => 'description',
            default => null,
        };
    }

    private function describe(Career $career, ?string $detail): string
    {
        if ($detail !== null) {
            return "{$career->title}: {$career->{$detail}}";
        }

        return "{$career->title}\n{$career->description}\n\nWhat to study: {$career->courses}\nSkills: {$career->skills}\nTry this: {$career->first_step}";
    }

    private function namedCareers(string $text, Collection $careers): Collection
    {
        return $careers->map(function (Career $career) use ($text) {
            $phrases = [$this->normalize($career->title), $career->keywords[0]];
            $offsets = array_filter(array_map(
                fn (string $phrase) => strpos(" {$text} ", " {$phrase} "),
                $phrases
            ), fn ($offset) => $offset !== false);

            return ['career' => $career, 'offset' => $offsets ? min($offsets) : null];
        })->whereNotNull('offset')->sortBy('offset')->pluck('career')->values();
    }

    private function positions(string $text): array
    {
        $text = preg_replace('/\boptions? ([0-9]+) and ([0-9]+)\b/', 'option $1 and option $2', $text);
        $text = preg_replace('/\b(first|second|third) (one|option)\b/', '$1', $text);
        $text = preg_replace('/\bfirst step\b/', 'starter project', $text);
        preg_match_all('/\b(?:options?|number) ([0-9]+)\b|\b(first|second|third|1st|2nd|3rd)\b/', $text, $matches, PREG_SET_ORDER);

        return array_values(array_unique(array_map(fn (array $match) => match ($match[2] ?? '') {
            'first', '1st' => 0,
            'second', '2nd' => 1,
            'third', '3rd' => 2,
            default => (int) $match[1] - 1,
        }, $matches)));
    }

    private function matches(string $text, Career $career): array
    {
        $matches = array_values(array_filter($career->keywords, fn (string $word) => $this->hasPhrase($text, $word)));

        return array_values(array_filter($matches, fn (string $word) => !collect($matches)->contains(
            fn (string $other) => $other !== $word && $this->hasPhrase($other, $word)
        )));
    }

    private function hasPhrase(string $text, string $phrase): bool
    {
        return str_contains(" {$text} ", " {$phrase} ");
    }

    private function positiveInterests(string $text): string
    {
        return trim(preg_replace('/\b(?:don t (?:like|enjoy)|do not (?:like|enjoy)|dislike|hate|not interested in|but not)\s+.*?(?=\b(?:but (?!not\b)|however|i (?:like|enjoy|love|prefer))\b|$)/', '', $text));
    }

    private function normalize(string $text): string
    {
        $clean = trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($text)));
        $synonyms = [
            'write code|writing code|code|programming languages' => 'coding',
            'maths' => 'mathematics',
            'electrical engineers' => 'electrical engineer',
            'chemical engineers' => 'chemical process engineer',
            'software developers' => 'software developer',
            'data analysts' => 'data analyst',
            'data scientists' => 'data scientist',
            'civil engineers' => 'civil engineer',
            'mechanical engineers' => 'mechanical engineer',
        ];

        foreach ($synonyms as $pattern => $replacement) {
            $clean = preg_replace('/\b('.$pattern.')\b/', $replacement, $clean);
        }

        return $clean;
    }
}
