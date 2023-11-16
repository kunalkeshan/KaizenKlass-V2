import { useLoaderData } from "@remix-run/react";
import axios from "axios";
import { AssignmentCard } from "~/components/assignmentCard";
import { BackButton } from "~/components/backButton";
import { Dashboard } from "~/components/dashboard";

export default function assignments() {
  const { assignments }: { assignments: Assignment[] } = useLoaderData();
  //   console.log(assignments);
  return (
    <div className="bg-primary h-screen">
      <Dashboard>
        <div className="header w-full h-20 mb-10 flex justify-between items-center text-5xl">
          <BackButton />
          <div className="font-display text-highlightSecondary mb-7 text-5xl">
            Assignments
          </div>
        </div>
        <div className="flex flex-col space-y-7">
          {assignments.map((assignment) => (
            <AssignmentCard
              subject={assignment.subject}
              title={assignment.title}
              assignment_uuid={assignment.assignment_uuid}
              subject_uuid={assignment.subject_uuid}
            />
          ))}
        </div>
      </Dashboard>
    </div>
  );
}

export const loader = async () => {
  const url = `${process.env.PUBLIC_DOMAIN}/api/v1/get-assignment-subjects`;
  const resp = await axios.get(url);
  return resp.data;
};