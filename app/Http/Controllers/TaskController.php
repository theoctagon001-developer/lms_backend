<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Models\offered_courses;
use App\Models\section;
use App\Models\session;
use App\Models\student_offered_courses;
use App\Models\teacher_offered_courses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
class TaskController extends Controller
{

    public function fetchHonorsList(Request $request)
    {
        try {
            $request->validate([
                'offered_course_id' => 'required',
                'cgpa' => 'required',
            ]);
            $abc = [];
            $offered_course_id = $request->offered_course_id;

            $offered_course = offered_courses::with(['course'])->where('id', $offered_course_id)->first();
            $course = $offered_course->course;
            $shortForm = $course->description;
            $section_id_honor = (new section())->addNewSection($shortForm . 'HonorSection');

            $cgpa = $request->cgpa;
            $studentCourses = student_offered_courses::where('offered_course_id', $offered_course_id)
                ->with(['student', 'offeredCourse'])
                ->get();
            $data = [];

            foreach ($studentCourses as $studentCourse) {
                $student = $studentCourse->student;
                if ($student->cgpa < $cgpa) {
                    $abc[] = $studentCourse;
                } else {
                    $data[] = [
                        "student_offered_course_id" => $studentCourse->id,
                        "student_id" => $student->id,
                        "name" => $student->name,
                        "RegNo" => $student->RegNo,
                        "CGPA" => $student->cgpa,
                        "enrolled_in_section" => (new section())->getNameByID($studentCourse->section_id),
                        "include" => ($section_id_honor == $studentCourse->section_id) ? "yes" : "no"
                    ];
                }

            }

            return response()->json(
                $data
                ,
                200
            );
        } catch (Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Initialization failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateEnrollmentSectionForHonorSection(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);

            $record = student_offered_courses::with(['offeredCourse.course'])->where('id', $request->id)->first();
            $course = $record->offeredCourse->course;
            $shortForm = $course->description;

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enrollment record not found.'
                ], 404);
            }
            $sectiondata = $shortForm . 'HonorSection';
            $section_id = (new section())->addNewSection($sectiondata);
            if ($record->section_id == $section_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student is already in this section.'
                ], 409);
            }

            $record->section_id = $section_id;
            $record->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Enrollment section updated successfully.',
                'data' => [
                    'id' => $record->id,
                    'student_id' => $record->student_id,
                    'offered_course_id' => $record->offered_course_id,
                    'new_section_id' => $record->section_id,
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addTeacherOfferedCourse(Request $request)
    {
        try {
            $validated = $request->validate([
                'offered_course_id' => 'required|exists:offered_courses,id',
                'teacher_id' => 'required|exists:teacher,id',

            ]);

            $offered_course_id = $request->offered_course_id;

            $offered_course = offered_courses::with(['course'])->where('id', $offered_course_id)->first();
            $course = $offered_course->course;
            $shortForm = $course->description;
            $section_id_honor = (new section())->addNewSection($shortForm . 'HonorSection');


            $offeredCourseId = $validated['offered_course_id'];
            $teacherId = $validated['teacher_id'];
            $sectionId = $section_id_honor;

            $duplicateFull = teacher_offered_courses::where([
                'offered_course_id' => $offeredCourseId,
                'teacher_id' => $teacherId,
                'section_id' => $section_id_honor,
            ])->first();

            if ($duplicateFull) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate entry: This teacher is already assigned to this course-section.',
                ], 409);
            }

            $duplicateSectionCourse = teacher_offered_courses::where([
                'offered_course_id' => $offeredCourseId,
                'section_id' => $sectionId,
            ])->first();
            if ($duplicateSectionCourse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate entry: This course is already assigned to the selected section.',
                ], 409);
            }
            $newEntry = teacher_offered_courses::create([
                'offered_course_id' => $offeredCourseId,
                'teacher_id' => $teacherId,
                'section_id' => $sectionId,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Teacher assigned to course-section successfully.',
                'data' => $newEntry,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function endSessionAsPerHonor(Request $request)
    {
        try {

            $currentSessionId = (new session())->getCurrentSessionId();
            if ($currentSessionId == 0) {
                throw new Exception('No Active Session Found');
            }
            $session = session::find($currentSessionId);
            $session->end_date = '2025-07-07';

            
             $session->save();
           
            $name = 'Fall';
            if ($session->name == 'Fall') {
                $name = 'Spring';
            } else if ($session->name == 'Spring') {
                $name = 'Summer';
            } else if ($session->name == 'Summer') {
                $name = 'Fall';
            }
            $start_date = date('y:m:d');
            $end_date = '';
            
            if ($name == 'Summer') {
             $start_date = date('y:m:d');
             $end_date = '2025-09-12';
            } else {
                  $start_date = '2025-09-12';
                  $end_date = '2026-01-02';
            }
           $session_new=session::updateOrCreate(
                [
                    'name' => $name,
                    'year' =>'2025',
                ],
                [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]
            );
            //  return $session;
         
            $enrolledCourse = student_offered_courses::with(['offeredCourse', 'section', 'student'])
                ->whereHas('offeredCourse', function ($query) use ($currentSessionId) {
                    $query->where('session_id', $currentSessionId);
                })->whereHas('section', function ($query) {
                    $query->where('semester', 'Honor');
                })
                ->get();
            $Success = [];
            $Error = [];
            foreach ($enrolledCourse as $enroll) {
                try {


                    $student_offered_course_id = $enroll->id;
                    $student_regular_section_id = $enroll->student->section_id;
                    $name = $enroll->student->name;
                    $regno = $enroll->student->RegNo;
                    $honor_section = (new section())->getNameByID($enroll->section_id);
                    $regular_section = (new section())->getNameByID($enroll->student->section_id);
                    $course = Course::where('id', $enroll->offeredCourse->course_id)->first();
                    $course_name = $course->name;

                    $enroll->section_id = $student_regular_section_id;
                    $enroll->save();

                    $Success[] = [
                        "status" => "Successfully to Move Student From Honor to Regular Section",
                        "Day" => $name . ' ( ' . $regno . ' )',
                        "Time" => $course_name,
                        "Record" => "The Student " . $name . " ( " . $regno . " ) is been moved to regular section " . $regular_section . " from the Honor Section " . $honor_section,
                    ];
                } catch (Exception $e) {
                    $Error[] = [
                        "Day" => "ERROR ",
                        "Time" => "ERROR",
                        "Raw Data" => "ERROR"
                    ];
                }
            }


            $successCount = count($Success);
            $errorCount = count($Error);
            return response()->json(
                [
                    'Message' => 'Data Inserted Successfully !',
                    'data' => [
                        "Successfully Added Records Count :" => $successCount,
                        "Faulty Records Count :" => $errorCount,
                        "Sucess" => $Success,
                        "Error" => $Error,
                    ]
                ],
                200
            );
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
     public function End(Request $request)
    {
        try {

            $currentSessionId = (new session())->getCurrentSessionId();
            if ($currentSessionId == 0) {
                throw new Exception('No Active Session Found');
            }
            $session = session::find($currentSessionId);
            $session->end_date = '2025-07-07';

            
             $session->save();
           
            $name = 'Fall';
            if ($session->name == 'Fall') {
                $name = 'Spring';
            } else if ($session->name == 'Spring') {
                $name = 'Summer';
            } else if ($session->name == 'Summer') {
                $name = 'Fall';
            }
            $start_date = date('y:m:d');
            $end_date = '';
            
            if ($name == 'Summer') {
             $start_date = date('y:m:d');
             $end_date = '2025-09-12';
            } else {
                  $start_date = '2025-09-12';
                  $end_date = '2026-01-02';
            }
           $session_new=session::updateOrCreate(
                [
                    'name' => $name,
                    'year' =>'2025',
                ],
                [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]
            );
            //  return $session;
         
            $enrolledCourse = student_offered_courses::with(['offeredCourse', 'section', 'student'])
                ->whereHas('offeredCourse', function ($query) use ($currentSessionId) {
                    $query->where('session_id', $currentSessionId);
                })->whereHas('section', function ($query) {
                    $query->where('semester', 'Honor');
                })
                ->get();
            $Success = [];
            $Error = [];
            foreach ($enrolledCourse as $enroll) {
                try {


                    $student_offered_course_id = $enroll->id;
                    $student_regular_section_id = $enroll->student->section_id;
                    $name = $enroll->student->name;
                    $regno = $enroll->student->RegNo;
                    $honor_section = (new section())->getNameByID($enroll->section_id);
                    $regular_section = (new section())->getNameByID($enroll->student->section_id);
                    $course = Course::where('id', $enroll->offeredCourse->course_id)->first();
                    $course_name = $course->name;

                    $enroll->section_id = $student_regular_section_id;
                    $enroll->save();

                    $Success[] = [
                        "data" => "Successfully to Move Student From Honor to Regular Section",
                        "log" => $name . ' ( ' . $regno . ' )',
                        "course" => $course_name,
                        "pro" => "The Student " . $name . " ( " . $regno . " ) is been moved to regular section " . $regular_section . " from the Honor Section " . $honor_section,
                    ];
                } catch (Exception $e) {
                    $Error[] = [
                        "Day" => "ERROR ",
                        "Time" => "ERROR",
                        "Raw Data" => "ERROR"
                    ];
                }
            }


            $successCount = count($Success);
            $errorCount = count($Error);
            return response()->json(
               $Success,
                200
            );
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
//   return $session;