<?php
namespace App\Services;

use App\Exceptions\SubjectNotFoundException;
use App\Models\Assignment;
use App\Models\Subject;
use App\Models\User;
use App\Services\SubjectService;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class AssignmentService{
    // todo: allow multiple links, allow both drive links and content in the future
    public function __construct(protected SubjectService $subjectService){
        

    }
    private function convertDriveLinkToDownloadLink(string $originalLink): ?string
{
    $fileIdRegex = '/\/d\/(.+?)\/|id=(.+?)&|&id=(.+?)($|&)/';
    if (preg_match($fileIdRegex, $originalLink, $match)) {
        $fileId = $match[1] ?? $match[2] ?? $match[3];
        $downloadLink = "https://drive.google.com/uc?export=download&id={$fileId}";
        return $downloadLink;
    }

    throw new InvalidArgumentException("Invalid Google Drive link format");
}



    public function getAssignmentId($assignmentUuid){
        $actualAssignmentId = Assignment::where("assignment_uuid", $assignmentUuid)->first()->id;
        return $actualAssignmentId;
    }

    public function getAssignmentsBySubject(string $subjectUuid){
    $subject = Subject::where('subject_uuid', $subjectUuid)->first();

    if (!$subject) {
        return response()->json(["error" => "Subject not found"], 404);
    }
    $subjectId = $this->subjectService->getSubjectId($subjectUuid);
    $assignments = Assignment::select(["title","assignment_uuid"])->where("subject_id",$subjectId)->orderBy("created_at","DESC")->get();
    $result = ["assignments" => $assignments,"subjectName" => $subject["subject"]];
    return $result;
    }

    public function addAssignment(string $title,?string $description,string $subjectUuid,?string $content = null, ?string $link = null,?string $deadline = null){
        if (!Subject::where("subject_uuid", $subjectUuid)->exists()) {
            throw new SubjectNotFoundException(message:"Invalid Subject uuid",code:400);
        }
        $subjectId = $this->subjectService->getSubjectId($subjectUuid);
    
        $assignmentData = [
            "title" => $title,
            "description" => $description,
            "subject_id" => $subjectId,
            "assignment_uuid" => Uuid::uuid4(),
        ];

        if($content){
            $assignmentData["content"] = $this->convertDriveLinkToDownloadLink($content);
        }
        if($link){
            $assignmentData["link"] = $link;
        }
        if($deadline){
            $assignmentData["deadline"] = Carbon::parse($deadline);
        }
    
        $assignment = Assignment::create($assignmentData);
        return $assignment;

    }

    public function editAssignment(string $assignmentUuid, array $data){

        $assigmentId = $this->getAssignmentId($assignmentUuid);
        $assignment = Assignment::where("id", $assigmentId)->first();
            
        if (!$assignment) {
            throw new Exception(message:"assignment not found!",code:404);
            return null;
        }
        if (isset($data['title'])) {
            $assignment->title = $data['title'];
        }
            
        if (isset($data['description'])) {
            $assignment->description = $data['description'];
        }
        if (isset($data['subject_uuid'])) {
            $subjectId = $this->subjectService->getSubjectId($data['subject_uuid']);
            $assignment->subject_id = $subjectId;
        }
            
        if (isset($data['link'])) {
            $assignment->link = $data['link'];
        }
            
        if (isset($data['content'])) {
            $assignment->content = $data['content'];
        }
        if (isset($data['deadline'])) {
            $assignment->deadline = Carbon::parse($data['deadline']);
        }
            
        $assignment->save();
            
        return $assignment;
  
        }

    public function deleteAssignment(string $assignmentUuid){
    if (!Assignment::where("assignment_uuid", $assignmentUuid)->exists()) {
        return response()->json(["message" => "Assignment not found"], 404);
    }
    $assignmentId = $this->getAssignmentId($assignmentUuid);

    // Find the assignment by its ID
    $assignment = Assignment::find($assignmentId);

    if (!$assignment) {
        return response()->json(["error" => "Assignment not found"], 404);
    }

    $assignment->delete();
        }

        public function getSolutionsByAssignment($assignmentUuid)
        {
            $assignment = Assignment::with(["solutions"])
                ->where("assignment_uuid", $assignmentUuid)
                ->first();
    
            if (!$assignment) {
                return response()->json(["message" => "Assignment not found"], 404);
            }
            $subjectDetails = $this->subjectService->getSubjectDetails($assignment->subject_id);
    
            $solutionsData = [];
            foreach ($assignment->solutions as $solution) {
                $user = User::select("name", "user_uuid")->where("id", $solution->user_id)->first();
                $username = $user->name;
                $userUuid = $user->user_uuid;
    
                $solutionsData[] = [
                    "description" => $solution->description,
                    "solution_uuid" => $solution->solution_uuid,
                    "username" => $username,
                    "user_uuid" => $userUuid,
                    "content" => $solution->content
                ];
            }
            // Return the assignment details without file content
            return [
                "assignment" => [
                    "title" => $assignment->title,
                    "description" => $assignment->description,
                    "link" => $assignment->link,
                    "assignment_uuid" => $assignment->assignment_uuid,
                    "content" => $assignment->content,
                    "subject" => $subjectDetails->subject,
                    "subject_uuid" => $subjectDetails->subject_uuid

                ],
                "solutions" => $solutionsData
            ];
        }
        public function getAssignmentsWithSubjects(){
            $assignments = Assignment::join("subjects","subjects.id","=","assignments.subject_id")->select("assignments.title","assignments.assignment_uuid","subjects.subject","subjects.subject_uuid")->orderBy("assignments.id","DESC")->paginate(5);
            return $assignments;
        }
        public function test(){
            $assignments = Assignment::join("subjects","subjects.id","=","assignments.subject_id")->select("assignments.title","assignments.assignment_uuid","subjects.subject","subjects.subject_uuid")->paginate(5);
            return $assignments;
        }
        public function getAssignmentsWithDeadline() {
            $currentDateTime = Carbon::now();
            $assignments = Assignment::select("title", "deadline", "assignment_uuid")
                ->whereNotNull("deadline")->where("deadline",">",$currentDateTime)
                ->get();
            return $assignments;
        }
    }
    // todo: paginate api calls for assignments route