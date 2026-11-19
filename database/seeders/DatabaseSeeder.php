<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['Software Developer', 'Computing', 'software development|software engineering|computer science|coding|programming|web|apps|building|python|javascript', 'Design and maintain applications people use at work, school, or home.', 'Computer science, software engineering, data structures, databases.', 'Programming, Git, debugging, testing.', 'Build a course timetable app and ask classmates to test it.'],
            ['Data Analyst', 'Mathematics and Computing', 'data analytics|data analysis|statistics|math|mathematics|data|spreadsheets|charts|patterns|excel|sql|python', 'Turn data into summaries that help teams make decisions.', 'Statistics, mathematics, computer science, economics, or related coursework.', 'Excel, SQL, data cleaning, visualisation and communicating findings.', 'Chart one indicator from a public Nigerian statistics dataset and explain what you found.'],
            ['Data Scientist', 'Mathematics and Computing', 'data science|machine learning|ai|statistics|math|mathematics|python|data|patterns|research', 'Use statistics and models to explore patterns and make predictions.', 'Statistics, linear algebra, programming and machine learning.', 'Python or R, experimental design, model evaluation and data cleaning.', 'Pick a public dataset and compare a simple prediction baseline with a regression model.'],
            ['Cybersecurity Analyst', 'Computing', 'cyber security|cybersecurity|networks|security|computer science|detecting|linux', 'Monitor systems for suspicious activity and help organisations protect their networks and information.', 'Computer science, information technology, networking and security fundamentals.', 'Networking, log interpretation, incident documentation and risk assessment.', 'Use sample system logs to write a short incident report with a timeline.'],
            ['Bioinformatician', 'Life Sciences and Computing', 'bioinformatics|computational biology|biology|genetics|statistics|coding|python|research|data', 'Use computing and statistics to study biological data.', 'Biology, biochemistry, computer science or statistics, with courses in genetics and programming.', 'Python or R, statistics, data visualisation and scientific communication.', 'Visualise an openly available plant biodiversity dataset and document your analysis.'],
            ['Civil Engineer', 'Engineering', 'civil engineering|construction|structures|infrastructure|design|drawing|physics|math|mathematics', 'Help design and maintain roads, buildings, bridges and other infrastructure.', 'Civil engineering, structural analysis, surveying and materials science.', 'CAD, interpreting drawings, technical writing and teamwork.', 'Use a public map to sketch a campus drainage plan and explain your assumptions.'],
            ['Electrical Engineer', 'Engineering', 'electrical engineering|electronics|circuits|power|energy|physics|math|mathematics|design', 'Design and test electrical systems for power, communications or electronics.', 'Electrical engineering, circuit theory, electronics and power systems.', 'Circuit modelling, testing, documentation and electrical safety.', 'Simulate a low-voltage lighting circuit and write up the results.'],
            ['Mechanical Engineer', 'Engineering', 'mechanical engineering|machines|mechanics|physics|design|manufacturing|robotics|building', 'Design and improve moving parts, machines and mechanical systems.', 'Mechanical engineering, mechanics, thermodynamics and materials science.', 'CAD, modelling, prototyping and documenting design choices.', 'Model a simple mechanical linkage in CAD and compare two designs.'],
            ['Chemical Process Engineer', 'Engineering', 'chemical engineering|chemistry|manufacturing|process|materials|math|mathematics|physics', 'Improve industrial processes that convert raw materials into products.', 'Chemical engineering, thermodynamics, fluid mechanics and process control.', 'Process modelling, data analysis, safety awareness and technical drawing.', 'Draw a process-flow diagram for a small water treatment facility using published information.'],
            ['Environmental Scientist', 'Environmental Science', 'environmental science|environment|climate|ecology|water|biology|chemistry|fieldwork|sustainability', 'Study environmental conditions and help organisations manage their impact.', 'Environmental science, ecology, geology, chemistry or geography.', 'Field observation, GIS, data interpretation and report writing.', 'Map publicly documented flood-prone areas in your community and summarise possible factors.'],
            ['Laboratory Quality Analyst', 'Physical and Life Sciences', 'lab|laboratory|chemistry|biology|experiments|quality|testing|biochemistry', 'Check that samples and products meet documented quality requirements.', 'Chemistry, biochemistry or a related laboratory science.', 'Measurement, record keeping, quality control and data interpretation.', 'Use sample measurement data to create a control chart and flag unusual readings.'],
            ['Geophysicist', 'Physical Sciences', 'geophysics|physics|earth science|geology|fieldwork|math|mathematics|modelling', 'Use physical measurements and models to study the Earth and its subsurface.', 'Physics, geophysics, geology and applied mathematics.', 'Signal analysis, mapping, numerical modelling and field documentation.', 'Plot publicly available earthquake or elevation data and summarise the patterns.'],
        ];

        foreach ($rows as [$title, $field, $keywords, $description, $courses, $skills, $firstStep]) {
            Career::updateOrCreate(['title' => $title], [
                'field' => $field,
                'keywords' => explode('|', $keywords),
                'description' => $description,
                'courses' => $courses,
                'skills' => $skills,
                'first_step' => $firstStep,
            ]);
        }
    }
}
