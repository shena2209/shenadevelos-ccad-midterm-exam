import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type RecordType = 'students' | 'teachers' | 'subjects' | 'sections' | 'grades';

interface RecordOptions {
    students: { id: number; name: string; lrn: string; grade_level: string; section_name: string }[];
    teachers: { id: number; name: string }[];
    subjects: { id: number; name: string; code: string; teacher_id: number | null; teacher_name: string | null }[];
    sections: { id: number; name: string }[];
}

interface CreateRecordDialogProps {
    options: RecordOptions;
    onCreated: (recordType: RecordType) => void;
}

const recordTypes: { id: RecordType; label: string }[] = [
    { id: 'students', label: 'Student' },
    { id: 'teachers', label: 'Teacher' },
    { id: 'subjects', label: 'Subject' },
    { id: 'sections', label: 'Section' },
    { id: 'grades', label: 'Grade' },
];

const inputClassName =
    'h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-black outline-none transition placeholder:text-black focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20';

export default function CreateRecordDialog({ options, onCreated }: CreateRecordDialogProps) {
    const [open, setOpen] = useState(false);
    const [recordType, setRecordType] = useState<RecordType>('students');
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        lrn: '',
        grade_level: '',
        code: '',
        student_id: '',
        subject_id: '',
        teacher_id: '',
        section_id: '',
        prelim_grade: '',
        midterm_grade: '',
        final_grade: '',
    });

    function chooseType(type: RecordType) {
        setRecordType(type);
        reset();
        clearErrors();
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post(route(`dashboard.records.${recordType}.store`), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
                onCreated(recordType);
            },
        });
    }

    function fieldError(field: string) {
        return <InputError className="font-medium text-black" message={errors[field as keyof typeof errors]} />;
    }

    function numberField(field: 'prelim_grade' | 'midterm_grade' | 'final_grade', label: string, min: number, max: number) {
        return (
            <div className="grid gap-1.5">
                <Label htmlFor={field} className="text-black">
                    {label}
                </Label>
                <Input
                    id={field}
                    type="number"
                    min={min}
                    max={max}
                    step="0.01"
                    required
                    value={data[field]}
                    onChange={(event) => setData(field, event.target.value)}
                    className={inputClassName}
                />
                {fieldError(field)}
            </div>
        );
    }

    function selectField(
        field: 'student_id' | 'subject_id' | 'teacher_id' | 'section_id',
        label: string,
        items: { id: number; label: string }[],
        emptyLabel: string,
        required = true,
    ) {
        return (
            <div className="grid gap-1.5">
                <Label htmlFor={field} className="text-black">
                    {label}
                </Label>
                <select
                    id={field}
                    required={required}
                    value={data[field]}
                    onChange={(event) => setData(field, event.target.value)}
                    className={inputClassName}
                >
                    <option className="bg-white text-black" value="">
                        {emptyLabel}
                    </option>
                    {items.map((item) => (
                        <option className="bg-white text-black" key={item.id} value={item.id}>
                            {item.label}
                        </option>
                    ))}
                </select>
                {fieldError(field)}
            </div>
        );
    }

    const title = recordTypes.find((type) => type.id === recordType)?.label ?? 'Record';

    return (
        <>
            <Button
                type="button"
                onClick={() => {
                    chooseType('students');
                    setOpen(true);
                }}
                className="h-10 rounded-xl border border-emerald-700 bg-emerald-100 px-4 text-sm font-medium text-black hover:bg-emerald-200"
            >
                Add or create data
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto rounded-2xl border-slate-200 bg-white text-black sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="text-black">Add school data</DialogTitle>
                        <DialogDescription className="text-black">Create a student, teacher, subject, section, or grade record.</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-5" aria-label="Record type">
                        {recordTypes.map((type) => (
                            <button
                                key={type.id}
                                type="button"
                                onClick={() => chooseType(type.id)}
                                className={`rounded-lg px-2 py-2 text-sm font-medium transition ${
                                    recordType === type.id
                                        ? 'bg-emerald-100 text-black ring-1 ring-emerald-700'
                                        : 'bg-slate-100 text-black hover:bg-slate-200'
                                }`}
                                aria-pressed={recordType === type.id}
                            >
                                {type.label}
                            </button>
                        ))}
                    </div>

                    <form onSubmit={submit} className="mt-1 grid gap-4">
                        {recordType === 'students' && (
                            <>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="student-name" className="text-black">
                                        Full name
                                    </Label>
                                    <Input
                                        id="student-name"
                                        required
                                        maxLength={150}
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        className={inputClassName}
                                    />
                                    {fieldError('name')}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="lrn" className="text-black">
                                        Learner reference number (LRN)
                                    </Label>
                                    <Input
                                        id="lrn"
                                        required
                                        inputMode="numeric"
                                        maxLength={12}
                                        pattern="[0-9]{12}"
                                        placeholder="12 digits"
                                        value={data.lrn}
                                        onChange={(event) => setData('lrn', event.target.value.replace(/\D/g, '').slice(0, 12))}
                                        className={inputClassName}
                                    />
                                    {fieldError('lrn')}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="grade_level" className="text-black">
                                        Grade level
                                    </Label>
                                    <select
                                        id="grade_level"
                                        required
                                        value={data.grade_level}
                                        onChange={(event) => setData('grade_level', event.target.value)}
                                        className={inputClassName}
                                    >
                                        <option className="bg-white text-black" value="">
                                            Select a grade level
                                        </option>
                                        {Array.from({ length: 12 }, (_, index) => `Grade ${index + 1}`).map((grade) => (
                                            <option className="bg-white text-black" key={grade} value={grade}>
                                                {grade}
                                            </option>
                                        ))}
                                    </select>
                                    {fieldError('grade_level')}
                                </div>
                                {selectField(
                                    'section_id',
                                    'Section',
                                    options.sections.map((section) => ({ id: section.id, label: section.name })),
                                    'Select a section',
                                )}
                            </>
                        )}

                        {recordType === 'teachers' && (
                            <div className="grid gap-1.5">
                                <Label htmlFor="teacher-name" className="text-black">
                                    Full name
                                </Label>
                                <Input
                                    id="teacher-name"
                                    required
                                    maxLength={150}
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    className={inputClassName}
                                />
                                {fieldError('name')}
                            </div>
                        )}

                        {recordType === 'subjects' && (
                            <>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="subject-name" className="text-black">
                                        Subject name
                                    </Label>
                                    <Input
                                        id="subject-name"
                                        required
                                        maxLength={150}
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        className={inputClassName}
                                    />
                                    {fieldError('name')}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="subject-code" className="text-black">
                                        Subject code
                                    </Label>
                                    <Input
                                        id="subject-code"
                                        required
                                        maxLength={20}
                                        value={data.code}
                                        onChange={(event) => setData('code', event.target.value)}
                                        className={inputClassName}
                                    />
                                    {fieldError('code')}
                                </div>
                                {selectField(
                                    'teacher_id',
                                    'Assigned teacher',
                                    options.teachers.map((teacher) => ({ id: teacher.id, label: teacher.name })),
                                    'No teacher assigned',
                                    false,
                                )}
                            </>
                        )}

                        {recordType === 'sections' && (
                            <div className="grid gap-1.5">
                                <Label htmlFor="section-name" className="text-black">
                                    Section name
                                </Label>
                                <Input
                                    id="section-name"
                                    required
                                    maxLength={100}
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    className={inputClassName}
                                />
                                {fieldError('name')}
                            </div>
                        )}

                        {recordType === 'grades' && (
                            <>
                                {selectField(
                                    'student_id',
                                    'Student',
                                    options.students.map((student) => ({
                                        id: student.id,
                                        label: `${student.name} · ${student.lrn} · ${student.grade_level}, ${student.section_name}`,
                                    })),
                                    'Select a student',
                                )}
                                {selectField(
                                    'subject_id',
                                    'Subject',
                                    options.subjects.map((subject) => ({
                                        id: subject.id,
                                        label: `${subject.code} · ${subject.name}${subject.teacher_name ? ` · ${subject.teacher_name}` : ''}`,
                                    })),
                                    'Select a subject',
                                )}
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {numberField('prelim_grade', 'Prelim', 0, 100)}
                                    {numberField('midterm_grade', 'Midterm', 0, 100)}
                                    {numberField('final_grade', 'Final', 0, 100)}
                                </div>
                                <p className="text-sm leading-5 text-black">
                                    Grades are averaged automatically. A final average of 75 or higher is marked as passed. The student’s section and
                                    the subject’s assigned teacher are used for this record.
                                </p>
                            </>
                        )}

                        <DialogFooter className="mt-2 gap-2 sm:gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                                disabled={processing}
                                className="border-slate-300 bg-white text-black hover:bg-slate-100 hover:text-black"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="border border-emerald-700 bg-emerald-100 text-black hover:bg-emerald-200"
                            >
                                {processing ? 'Saving…' : `Create ${title.toLowerCase()}`}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
