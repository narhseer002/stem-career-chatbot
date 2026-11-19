<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerChatTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_guided_recommendations_support_numbered_follow_ups(): void
    {
        $this->get('/')->assertOk()->assertSee('STEM Pathways');
        $this->ask('Help me choose')->assertSee('What stage');
        $this->ask('Final-year student')->assertSee('Which STEM subjects');
        $this->ask('Biology and statistics')->assertSee('What kind of work');
        $this->ask('I enjoy writing code')->assertSee('1. Bioinformatician')->assertSee('final-year project');
        $this->ask('What skills do I need for option 1?')->assertSee('Bioinformatician: Python or R');

        $this->deleteJson('/chat')->assertNoContent();
        $this->ask('What skills do I need for option 1?')->assertSee('Tell me a STEM subject');
    }

    public function test_named_queries_and_comparisons_use_curated_profiles(): void
    {
        $this->ask('What should I study to become a Civil Engineer?')->assertSee('structural analysis');
        $this->ask('Compare Data Analyst and Data Scientist')->assertSee('Data Analyst:')->assertSee('Data Scientist:');
        $this->ask('What projects can I try?')->assertSee('Data Analyst: Chart')->assertSee('Data Scientist: Pick');
    }

    public function test_unrecognised_input_and_invalid_requests_are_handled(): void
    {
        $this->ask('Tell me tomorrowâ€™s weather')->assertSee('Tell me a STEM subject');
        $this->postJson('/chat', ['message' => ''])->assertUnprocessable();
        $this->postJson('/chat', ['message' => str_repeat('x', 601)])->assertUnprocessable();
    }

    public function test_numbered_options_stay_stable_and_context_follows_the_selected_career(): void
    {
        $this->ask('I like biology, statistics and coding')->assertSee('1. Bioinformatician');
        $this->ask('Tell me more about the first one')->assertSee('Use computing and statistics');
        $this->ask('What should I study?')->assertSee('Bioinformatician: Biology');
        $this->ask('Compare options 1 and 2')->assertSee('Bioinformatician:')->assertSee('Data Analyst:');
        $this->ask('Skills for option 3')->assertSee('Data Scientist:');
        $this->ask('Skills for option 4')->assertSee('outside the last set');
    }

    public function test_degree_questions_and_negated_interests_generate_recommendations(): void
    {
        $this->ask('What can I do with a maths degree?')->assertSee('Based on the interests');
        $this->ask("I don't like coding but I enjoy chemistry")->assertSee('Matches your interests in chemistry')
            ->assertDontSee('Matches your interests in coding');
        $this->ask('Show me vacancies')->assertSee('check a recent local job listing');
    }

    public function test_comparison_clarifies_and_transcript_survives_a_page_reload(): void
    {
        $this->ask('Compare Civil Engineer')->assertSee('Which career should I compare with Civil Engineer?');
        $this->ask('Electrical Engineer')->assertSee('Civil Engineer:')->assertSee('Electrical Engineer:');
        $this->get('/')->assertSee('Compare Civil Engineer')->assertSee('Electrical Engineer:');
        $this->deleteJson('/chat')->assertNoContent();
        $this->get('/')->assertDontSee('Compare Civil Engineer');
    }

    private function ask(string $message): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/chat', ['message' => $message])->assertOk();
    }
}
