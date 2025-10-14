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


        // --- 20 Student Users ---
        $studentNames = [
            ['Juan', 'Dela Cruz'],
            ['Maria', 'Lopez'],
            ['Jose', 'Santos'],
            ['Ana', 'Reyes'],
            ['Carlo', 'Garcia'],
            ['Liza', 'Ramos'],
            ['Mark', 'Fernandez'],
            ['Ella', 'Torres'],
            ['Paolo', 'Mendoza'],
            ['Grace', 'Bautista'],
            ['Rafael', 'Navarro'],
            ['Bianca', 'Cruz'],
            ['Miguel', 'Aquino'],
            ['Patricia', 'Villanueva'],
            ['Leo', 'Domingo'],
            ['Sofia', 'Castillo'],
            ['Daniel', 'Perez'],
            ['Clarisse', 'Santiago'],
            ['Gabriel', 'De Leon'],
            ['Angela', 'Flores'],
        ];

        foreach ($studentNames as $index => $name) {
            User::factory()->create([
                'first_name' => $name[0],
                'last_name'  => $name[1],
                'role'       => 'user',
                'email'      => 'user' . ($index + 1) . '@gmail.com',
                'password'   => Hash::make('123'),
            ]);
        }

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
            ['ITCC100', 'Introduction to Computing', 3, '1st', 1, []],
            ['ITCC101', 'Fundamentals of Programming', 3, '1st', 1, []],
            ['GE01', 'Understanding the Self', 3, '1st', 1, []],
            ['GE04', 'Mathematics in the Modern World', 3, '1st', 1, []],
            ['GE05', 'Purposive Communication', 3, '1st', 1, []],
            ['ITELEC100', 'Discrete Mathematics for IT', 3, '1st', 1, []],
            ['PATHFit1', 'Movement Competency Training', 2, '1st', 1, []],
            ['NSTP1', 'National Service Training Program 1', 3, '1st', 1, []],

            ['ITCC102', 'Intermediate Programming', 3, '2nd', 1, ['ITCC101']],
            ['ITPC100', 'Fundamentals of Web Development', 3, '2nd', 1, []],
            ['ITPC101', 'Computer System Servicing', 3, '2nd', 1, []],
            ['ITPC112', 'Visual Graphics Design', 3, '2nd', 1, []],
            ['GE07', 'Science, Technology and Society', 3, '2nd', 1, []],
            ['GE09', 'Life and Works of Rizal', 3, '2nd', 1, []],
            ['GE08', 'Ethics', 3, '2nd', 1, []],
            ['PATHFit2', 'Exercise-based Fitness Activities', 2, '2nd', 1, ['PATHFit1']],
            ['NSTP2', 'National Service Training Program 2', 3, '2nd', 1, ['NSTP1']],

            // Year 2
            ['ITCC103', 'Data Structures & Algorithms', 3, '1st', 2, ['ITCC102']],
            ['ITPC102', 'Object-Oriented Programming', 3, '1st', 2, ['ITCC102']],
            ['ITPC103', 'Database Management System', 3, '1st', 2, ['ITCC101']],
            ['ITPC104', 'Computer Networking 1 - Fundamentals', 3, '1st', 2, ['ITPC101']],
            ['GE02', 'Readings in the Philippine History', 3, '1st', 2, []],
            ['GE10', 'Philippine Indigenous Community', 3, '1st', 2, []],
            ['GE03', 'The Contemporary World', 3, '1st', 2, []],
            ['PATHFit3', 'Dance, Sports, Martial Arts, Group Exercise 1', 2, '1st', 2, ['PATHFit2']],

            ['ITCC104', 'Information Management - RDBMS', 3, '2nd', 2, ['ITPC103']],
            ['ITPC105', 'Event Driven Programming', 3, '2nd', 2, ['ITCC103', 'ITPC102', 'ITPC103']],
            ['ITPC106', 'Advanced Web Development', 3, '2nd', 2, ['ITPC100', 'ITPC103']],
            ['ITPC107', 'Human Computer Interaction', 3, '2nd', 2, ['ITPC112', 'ITCC101']],
            ['ITPC108', 'Python Programming', 3, '2nd', 2, ['ITCC101']],
            ['GE06', 'Art Appreciation', 3, '2nd', 2, []],
            ['GEIT11', 'Sensors and Interfacing', 3, '2nd', 2, ['ITCC101']],
            ['PATHFit4', 'Dance, Sports, Martial Arts, Group Exercise 2', 2, '2nd', 2, ['PATHFit3']],

            // Year 3 (sample)
            ['CIP1101', 'Integrative Programming & Technologies 1', 3, '1st', 3, ['ITPC106']],
            ['CIT2201', 'Networking 1', 3, '1st', 3, ['ITPC 104']],
            ['CSA1101', 'Systems Analysis & Design', 3, '1st', 3, []],
            ['PPE1104', 'Physical Education 4', 2, '1st', 3, []],
            ['ZGEEL01', 'GE Elective 1', 3, '1st', 3, []],
            ['CIP1102', 'Integrative Programming & Technologies II', 3, '2nd', 3, ['CIP1101']],
            ['CIS3201', 'IS Strategy & Management', 3, '2nd', 3, []],
            ['CPP4980', 'Capstone Project & Research I', 3, '2nd', 3, []],
            ['ZGE1109', 'Life & Works of Rizal', 3, '2nd', 3, []],

            // Year 4 (sample)
            ['CIA1101', 'Information Assurance & Security I', 3, '1st', 4, []],
            ['CISEL02', 'Professional Elective 2', 3, '1st', 4, []],
            ['CISEL03', 'Professional Elective 3', 3, '1st', 4, []],
            ['CISEL04', 'Professional Elective 4', 3, '1st', 4, []],
            ['CPD4990', 'Capstone Project & Research II', 3, '1st', 4, []],
            ['Practicum', 'Internship / Practicum', 6, '2nd', 4, []],
            ['SeminarAndTour', 'Seminar & Tour', 3, '2nd', 4, []],
        ];

        // --- BSIS Subjects 1st–4th Year (template) ---
        $bsisSubjects = [
            // Year 1
            ['CIC1101', 'Introduction to Computing', 3, '1st', 1, []],
            ['CCP1101', 'Computer Programming 1', 3, '1st', 1, []],
            ['CIS1101', 'Fundamentals of Information Systems', 3, '1st', 1, []],
            ['MLC1101', 'Literacy / Civic Welfare / Military Science 1', 3, '1st', 1, []],
            ['PPE1101', 'Physical Education 1', 2, '1st', 1, []],
            ['ZGE1102', 'The Contemporary World', 3, '1st', 1, []],
            ['ZGE1108', 'Understanding the Self', 3, '1st', 1, []],

            ['CCP1102', 'Computer Programming 2', 3, '2nd', 1, ['CCP1101']],
            ['CDS1101', 'Data Structures & Algorithms', 3, '2nd', 1, ['CCP1102']],
            ['CSP1101', 'Social & Professional Issues in Computing', 3, '2nd', 1, []],
            ['MLC1102', 'Literacy / Civic Welfare / Military Science 2', 3, '2nd', 1, []],
            ['PPE1102', 'Physical Education 2', 2, '2nd', 1, ['PPE1101']],
            ['ZGE1101', 'Art Appreciation', 3, '2nd', 1, []],
            ['ZGE1104', 'Mathematics in the Modern World', 3, '2nd', 1, []],
            ['ZGE1106', 'Readings in Philippine History', 3, '2nd', 1, []],

            // Year 2
            ['CBM1101', 'Business Process Management', 3, '1st', 2, []],
            ['CCP1103', 'Computer Programming 3', 3, '1st', 2, ['CCP1102']],
            ['CDM1101', 'Discrete Mathematics for IS', 3, '1st', 2, []],
            ['CFD1101', 'Fundamentals of Database Systems', 3, '1st', 2, []],
            ['CIS2101', 'Accounting for IS', 3, '1st', 2, []],
            ['CIS2102', 'Enterprise Architecture', 3, '1st', 2, []],
            ['CQM1101', 'Quantitative Methods / Modeling', 3, '1st', 2, []],
            ['PPE1103', 'Physical Education 3', 2, '1st', 2, ['PPE1102']],

            ['CIM1101', 'Information Management', 3, '2nd', 2, ['CFD1101']],
            ['CIP1101', 'Integrative Programming & Technologies I', 3, '2nd', 2, []],
            ['CIS2201', 'Evaluation of Business Performance', 3, '2nd', 2, []],
            ['CSA1101', 'Systems Analysis & Prototyping', 3, '2nd', 2, []],
            ['PPE1104', 'Physical Education 4', 2, '2nd', 2, ['PPE1103']],
            ['ZGE1103', 'Ethics', 3, '2nd', 2, []],
            ['ZGE1105', 'Purposive Communication', 3, '2nd', 2, []],
            ['ZGEEL01', 'GE Elective 1', 3, '2nd', 2, []],

            // Year 3
            ['CHC1101', 'Human Computer Interaction', 3, '1st', 3, []],
            ['CIP1102', 'Integrative Programming & Technologies II', 3, '1st', 3, ['CIP1101']],
            ['CIS3101', 'Financial Management', 3, '1st', 3, []],
            ['CIS3102', 'IT Infrastructure & Network Technologies', 3, '1st', 3, []],
            ['CIS3103', 'Management Information Systems', 3, '1st', 3, []],
            ['CMR1101', 'Methods of Research for IS', 3, '1st', 3, []],
            ['ZGE1107', 'Science, Technology & Society', 3, '1st', 3, []],
            ['ZGEEL02', 'GE Elective 2', 3, '1st', 3, []],

            ['CDE1101', 'Application Development & Emerging Technologies', 3, '2nd', 3, []],
            ['CDT1101', 'Data Analytics', 3, '2nd', 3, []],
            ['CIS3201', 'IS Strategy & Management', 3, '2nd', 3, []],
            ['CIS3202', 'Technopreneurship', 3, '2nd', 3, []],
            ['CISEL01', 'Professional Elective 1', 3, '2nd', 3, []],
            ['CPP4980', 'Capstone Project & Research I', 3, '2nd', 3, []],
            ['ZGE1109', 'Life & Works of Rizal', 3, '2nd', 3, []],

            // Year 4
            ['CIA1101', 'Information Assurance & Security I', 3, '1st', 4, []],
            ['CISEL02', 'Professional Elective 2', 3, '1st', 4, []],
            ['CISEL03', 'Professional Elective 3', 3, '1st', 4, []],
            ['CISEL04', 'Professional Elective 4', 3, '1st', 4, []],
            ['CPD4990', 'Capstone Project & Research II', 3, '1st', 4, []],
            ['Practicum', 'Internship / Practicum', 6, '2nd', 4, []],
            ['SeminarTour', 'Seminar & Tour', 3, '2nd', 4, []],
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
