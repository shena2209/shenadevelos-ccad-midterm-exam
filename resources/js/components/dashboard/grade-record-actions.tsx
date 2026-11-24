import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface GradeRecord {
    id: number;
    student_id: number;
    subject_id: number;
    student_name: string;
    lrn: string;
    subject_name: string;
    subject_code: string;
    grade_level: string;
    section_name: string;
    prelim_grade: number | string | null;
    midterm_grade: number | string | null;
    final_grade: number | string | null;
    average_grade: number | string | null;
    pass_fail_status: 'Passed' | 'Failed' | null;
}

interface GradeRecordOptions {
    students: { id: number; name: string; lrn: string; grade_level: string; section_name: string }[];
    subjects: { id: number; name: string; code: string; teacher_id: number | null; teacher_name: string | null }[];
}

interface GradeRecordActionsProps {
    grade: GradeRecord;
    options: GradeRecordOptions;
    onView: () => void;
    onChanged: (action: 'updated' | 'deleted') => void;
}

export default function GradeRecordActions({ grade, options, onView, onChanged }: GradeRecordActionsProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const { data, setData, patch, processing, errors, clearErrors } = useForm({
        student_id: String(grade.student_id),
        subject_id: String(grade.subject_id),
        prelim_grade: String(grade.prelim_grade ?? ''),
        midterm_grade: String(grade.midterm_grade ?? ''),
        final_grade: String(grade.final_grade ?? ''),
    });
    const { delete: destroy, processing: deleting } = useForm({});

    function openEdit() {
        setData({
            student_id: String(grade.student_id),
            subject_id: String(grade.subject_id),
            prelim_grade: String(grade.prelim_grade ?? ''),
            midterm_grade: String(grade.midterm_grade ?? ''),
            final_grade: String(grade.final_grade ?? ''),
        });
        clearErrors();
        setEditOpen(true);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        patch(route('dashboard.records.grades.update', grade.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditOpen(false);
                onChanged('updated');
            },
        });
    }

    function deleteGrade() {
        destroy(route('dashboard.records.grades.destroy', grade.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteOpen(false);
                onChanged('deleted');
            },
        });
    }

    function gradeInput(field: 'prelim_grade' | 'midterm_grade' | 'final_grade', label: string) {
        const id = `grade-${grade.id}-${field}`;

        return (
            <div className="grid gap-1.5">
                <Label htmlFor={id}>{label}</Label>
                <Input
                    id={id}
                    type="number"
                    min={0}
                    max={100}
                    step="0.01"
                    value={data[field]}
                    onChange={(event) => setData(field, event.target.value)}
                />
                <InputError message={errors[field]} />
            </div>
        );
    }

    return (
        <>
            <div className="inline-flex items-center gap-1">
                <Button type="button" variant="ghost" size="icon" aria-label={`View grade for ${grade.student_name}`} onClick={onView}>
                    <Eye />
                </Button>
                <Button type="button" variant="ghost" size="icon" aria-label={`Edit grade for ${grade.student_name}`} onClick={openEdit}>
                    <Pencil />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="text-destructive hover:text-destructive"
                    aria-label={`Delete grade for ${grade.student_name}`}
                    onClick={() => setDeleteOpen(true)}
                >
                    <Trash2 />
                </Button>
            </div>

            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Edit grade record</DialogTitle>
                        <DialogDescription>Update the student, subject, or assessment grades.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor={`grade-${grade.id}-student`}>Student</Label>
                            <select
                                id={`grade-${grade.id}-student`}
                                required
                                value={data.student_id}
                                onChange={(event) => setData('student_id', event.target.value)}
                                className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                            >
                                {options.students.map((student) => (
                                    <option key={student.id} value={student.id}>
                                        {student.name} · {student.lrn} · {student.grade_level}, {student.section_name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.student_id} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor={`grade-${grade.id}-subject`}>Subject</Label>
                            <select
                                id={`grade-${grade.id}-subject`}
                                required
                                value={data.subject_id}
                                onChange={(event) => setData('subject_id', event.target.value)}
                                className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                            >
                                {options.subjects.map((subject) => (
                                    <option key={subject.id} value={subject.id}>
                                        {subject.code} · {subject.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.subject_id} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            {gradeInput('prelim_grade', 'Prelim')}
                            {gradeInput('midterm_grade', 'Midterm')}
                            {gradeInput('final_grade', 'Final')}
                        </div>
                        <p className="text-muted-foreground text-sm">
                            Leave an assessment blank to keep this record pending until all three grades are entered.
                        </p>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)} disabled={processing}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-emerald-700 text-white hover:bg-emerald-800">
                                {processing ? 'Saving…' : 'Save changes'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete grade record?</DialogTitle>
                        <DialogDescription>
                            This permanently deletes {grade.student_name}&apos;s grade for {grade.subject_name}. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setDeleteOpen(false)} disabled={deleting}>
                            Cancel
                        </Button>
                        <Button type="button" variant="destructive" onClick={deleteGrade} disabled={deleting}>
                            {deleting ? 'Deleting…' : 'Delete record'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
