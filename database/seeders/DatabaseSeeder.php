<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Subject;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin User ---
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'role'       => 'admin',
            'email'      => 'admin@gmail.com',
            'password'   => Hash::make('123'),
        ]);

        // --- Courses ---
        $bsit = Course::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'description' => 'Focus on computing, networking, software, etc.'
        ]);

        $bsis = Course::create([
            'code' => 'BSIS',
            'name' => 'Bachelor of Science in Information Systems',
            'description' => 'Focus on systems, business, IT integration.'
        ]);

        // --- Curriculums ---
        $bsitCurr = Curriculum::create([
            'course_id' => $bsit->id,
            'year_start' => 2025,
            'year_end' => 2029,
            'is_active' => true,
        ]);

        $bsisCurr = Curriculum::create([
            'course_id' => $bsis->id,
            'year_start' => 2025,
            'year_end' => 2029,
            'is_active' => true,
        ]);

        // --- BSIT Subjects 1st–4th Year (template) ---
        $bsitSubjects = [
            // Year 1
            ['ITCC 100', 'Introduction to Computing', 3, '1st', 1, []],
            ['ITCC 101', 'Fundamentals of Programming', 3, '1st', 1, []],
            ['GE 01', 'Understanding the Self', 3, '1st', 1, []],
            ['GE 04', 'Mathematics in the Modern World', 3, '1st', 1, []],
            ['GE 05', 'Purposive Communication', 3, '1st', 1, []],
            ['ITELEC 100', 'Discrete Mathematics for IT', 3, '1st', 1, []],
            ['PATHFit 1', 'Movement Competency Training', 2, '1st', 1, []],
            ['NSTP 1', 'National Service Training Program 1', 3, '1st', 1, []],

            ['ITCC 102', 'Intermediate Programming', 3, '2nd', 1, ['ITCC 101']],
            ['ITPC 100', 'Fundamentals of Web Development', 3, '2nd', 1, []],
            ['ITPC 101', 'Computer System Servicing', 3, '2nd', 1, []],
            ['ITPC 112', 'Visual Graphics Design', 3, '2nd', 1, []],
            ['GE 07', 'Science, Technology and Society', 3, '2nd', 1, []],
            ['GE 09', 'Life and Works of Rizal', 3, '2nd', 1, []],
            ['GE 08', 'Ethics', 3, '2nd', 1, []],
            ['PATHFit 2', 'Exercise-based Fitness Activities', 2, '2nd', 1, ['PATHFit 1']],
            ['NSTP 2', 'National Service Training Program 2', 3, '2nd', 1, ['NSTP 1']],

            // Year 2
            ['ITCC 103', 'Data Structures & Algorithms', 3, '1st', 2, ['ITCC 102']],
            ['ITPC 102', 'Object-Oriented Programming', 3, '1st', 2, ['ITCC 102']],
            ['ITPC 103', 'Database Management System', 3, '1st', 2, ['ITCC 101']],
            ['ITPC 104', 'Computer Networking 1 - Fundamentals', 3, '1st', 2, ['ITPC 101']],
            ['GE 02', 'Readings in the Philippine History', 3, '1st', 2, []],
            ['GE 10', 'Philippine Indigenous Community', 3, '1st', 2, []],
            ['GE 03', 'The Contemporary World', 3, '1st', 2, []],
            ['PATHFit 3', 'Dance, Sports, Martial Arts, Group Exercise 1', 2, '1st', 2, ['PATHFit 2']],

            ['ITCC 104', 'Information Management - RDBMS', 3, '2nd', 2, ['ITPC 103']],
            ['ITPC 105', 'Event Driven Programming', 3, '2nd', 2, ['ITCC 103', 'ITPC 102', 'ITPC 103']],
            ['ITPC 106', 'Advanced Web Development', 3, '2nd', 2, ['ITPC 100', 'ITPC 103']],
            ['ITPC 107', 'Human Computer Interaction', 3, '2nd', 2, ['ITPC 112', 'ITCC 101']],
            ['ITPC 108', 'Python Programming', 3, '2nd', 2, ['ITCC 101']],
            ['GE 06', 'Art Appreciation', 3, '2nd', 2, []],
            ['GE IT 11', 'Sensors and Interfacing', 3, '2nd', 2, ['ITCC 101']],
            ['PATHFit 4', 'Dance, Sports, Martial Arts, Group Exercise 2', 2, '2nd', 2, ['PATHFit 3']],

            // Year 3 (sample)
            ['CIP 1101', 'Integrative Programming & Technologies 1', 3, '1st', 3, ['ITPC 106']],
            ['CIT 2201', 'Networking 1', 3, '1st', 3, ['ITPC 104']],
            ['CSA 1101', 'Systems Analysis & Design', 3, '1st', 3, []],
            ['PPE 1104', 'Physical Education 4', 2, '1st', 3, []],
            ['ZGE EL01', 'GE Elective 1', 3, '1st', 3, []],
            ['CIP 1102', 'Integrative Programming & Technologies II', 3, '2nd', 3, ['CIP 1101']],
            ['CIS 3201', 'IS Strategy & Management', 3, '2nd', 3, []],
            ['CPP 4980', 'Capstone Project & Research I', 3, '2nd', 3, []],
            ['ZGE 1109', 'Life & Works of Rizal', 3, '2nd', 3, []],

            // Year 4 (sample)
            ['CIA 1101', 'Information Assurance & Security I', 3, '1st', 4, []],
            ['CIS EL02', 'Professional Elective 2', 3, '1st', 4, []],
            ['CIS EL03', 'Professional Elective 3', 3, '1st', 4, []],
            ['CIS EL04', 'Professional Elective 4', 3, '1st', 4, []],
            ['CPD 4990', 'Capstone Project & Research II', 3, '1st', 4, []],
            ['Practicum', 'Internship / Practicum', 6, '2nd', 4, []],
            ['Seminar & Tour', 'Seminar & Tour', 3, '2nd', 4, []],
        ];

        // --- BSIS Subjects 1st–4th Year (template) ---
        $bsisSubjects = [
            // Year 1
            ['CIC 1101', 'Introduction to Computing', 3, '1st', 1, []],
            ['CCP 1101', 'Computer Programming 1', 3, '1st', 1, []],
            ['CIS 1101', 'Fundamentals of Information Systems', 3, '1st', 1, []],
            ['MLC 1101', 'Literacy / Civic Welfare / Military Science 1', 3, '1st', 1, []],
            ['PPE 1101', 'Physical Education 1', 2, '1st', 1, []],
            ['ZGE 1102', 'The Contemporary World', 3, '1st', 1, []],
            ['ZGE 1108', 'Understanding the Self', 3, '1st', 1, []],

            ['CCP 1102', 'Computer Programming 2', 3, '2nd', 1, ['CCP 1101']],
            ['CDS 1101', 'Data Structures & Algorithms', 3, '2nd', 1, ['CCP 1102']],
            ['CSP 1101', 'Social & Professional Issues in Computing', 3, '2nd', 1, []],
            ['MLC 1102', 'Literacy / Civic Welfare / Military Science 2', 3, '2nd', 1, []],
            ['PPE 1102', 'Physical Education 2', 2, '2nd', 1, ['PPE 1101']],
            ['ZGE 1101', 'Art Appreciation', 3, '2nd', 1, []],
            ['ZGE 1104', 'Mathematics in the Modern World', 3, '2nd', 1, []],
            ['ZGE 1106', 'Readings in Philippine History', 3, '2nd', 1, []],

            // Year 2
            ['CBM 1101', 'Business Process Management', 3, '1st', 2, []],
            ['CCP 1103', 'Computer Programming 3', 3, '1st', 2, ['CCP 1102']],
            ['CDM 1101', 'Discrete Mathematics for IS', 3, '1st', 2, []],
            ['CFD 1101', 'Fundamentals of Database Systems', 3, '1st', 2, []],
            ['CIS 2101', 'Accounting for IS', 3, '1st', 2, []],
            ['CIS 2102', 'Enterprise Architecture', 3, '1st', 2, []],
            ['CQM 1101', 'Quantitative Methods / Modeling', 3, '1st', 2, []],
            ['PPE 1103', 'Physical Education 3', 2, '1st', 2, ['PPE 1102']],

            ['CIM 1101', 'Information Management', 3, '2nd', 2, ['CFD 1101']],
            ['CIP 1101', 'Integrative Programming & Technologies I', 3, '2nd', 2, []],
            ['CIS 2201', 'Evaluation of Business Performance', 3, '2nd', 2, []],
            ['CSA 1101', 'Systems Analysis & Prototyping', 3, '2nd', 2, []],
            ['PPE 1104', 'Physical Education 4', 2, '2nd', 2, ['PPE 1103']],
            ['ZGE 1103', 'Ethics', 3, '2nd', 2, []],
            ['ZGE 1105', 'Purposive Communication', 3, '2nd', 2, []],
            ['ZGE EL01', 'GE Elective 1', 3, '2nd', 2, []],

            // Year 3
            ['CHC 1101', 'Human Computer Interaction', 3, '1st', 3, []],
            ['CIP 1102', 'Integrative Programming & Technologies II', 3, '1st', 3, ['CIP 1101']],
            ['CIS 3101', 'Financial Management', 3, '1st', 3, []],
            ['CIS 3102', 'IT Infrastructure & Network Technologies', 3, '1st', 3, []],
            ['CIS 3103', 'Management Information Systems', 3, '1st', 3, []],
            ['CMR 1101', 'Methods of Research for IS', 3, '1st', 3, []],
            ['ZGE 1107', 'Science, Technology & Society', 3, '1st', 3, []],
            ['ZGE EL02', 'GE Elective 2', 3, '1st', 3, []],

            ['CDE 1101', 'Application Development & Emerging Technologies', 3, '2nd', 3, []],
            ['CDT 1101', 'Data Analytics', 3, '2nd', 3, []],
            ['CIS 3201', 'IS Strategy & Management', 3, '2nd', 3, []],
            ['CIS 3202', 'Technopreneurship', 3, '2nd', 3, []],
            ['CIS EL01', 'Professional Elective 1', 3, '2nd', 3, []],
            ['CPP 4980', 'Capstone Project & Research I', 3, '2nd', 3, []],
            ['ZGE 1109', 'Life & Works of Rizal', 3, '2nd', 3, []],

            // Year 4
            ['CIA 1101', 'Information Assurance & Security I', 3, '1st', 4, []],
            ['CIS EL02', 'Professional Elective 2', 3, '1st', 4, []],
            ['CIS EL03', 'Professional Elective 3', 3, '1st', 4, []],
            ['CIS EL04', 'Professional Elective 4', 3, '1st', 4, []],
            ['CPD 4990', 'Capstone Project & Research II', 3, '1st', 4, []],
            ['Practicum', 'Internship / Practicum', 6, '2nd', 4, []],
            ['Seminar & Tour', 'Seminar & Tour', 3, '2nd', 4, []],
        ];

        $this->insertSubjects($bsitCurr->id, $bsitSubjects);
        $this->insertSubjects($bsisCurr->id, $bsisSubjects);

        $this->command->info('✅ Seeded full 4-year BSIT & BSIS curriculum.');
    }

    private function insertSubjects($curriculumId, $subjects)
    {
        $map = [];

        foreach ($subjects as [$code, $name, $units, $sem, $year, $prereqs]) {
            $s = Subject::create([
                'curriculum_id' => $curriculumId,
                'code' => $code,
                'name' => $name,
                'units' => $units,
                'semester' => $sem,
                'year_level' => $year,
            ]);
            $map[$code] = $s;
        }

        foreach ($subjects as [$code, $name, $units, $sem, $year, $prereqs]) {
            if (!empty($prereqs)) {
                $ids = array_filter(array_map(fn($p) => $map[$p]->id ?? null, $prereqs));
                if (!empty($ids)) {
                    $map[$code]->prerequisites()->attach($ids);
                }
            }
        }
    }
}
