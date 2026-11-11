import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { CheckCircle2, Plus } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

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

const inputClassName = 'h-10';
const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring';

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
    const {
        data: sectionData,
        setData: setSectionData,
        post: postSection,
        processing: sectionProcessing,
        errors: sectionErrors,
        reset: resetSection,
    } = useForm({ name: '' });
    const [creatingSection, setCreatingSection] = useState(false);
    const [pendingSectionName, setPendingSectionName] = useState('');

    useEffect(() => {
        if (!pendingSectionName) return;

        const section = options.sections.find((section) => section.name === pendingSectionName);
        if (section) {
            setData('section_id', String(section.id));
            setPendingSectionName('');
            setCreatingSection(false);
            resetSection();
        }
    }, [options.sections, pendingSectionName, resetSection, setData]);

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

    function createSection() {
        if (!sectionData.name.trim()) return;

        postSection(route('dashboard.records.sections.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setPendingSectionName(sectionData.name.trim());
                onCreated('sections');
            },
        });
    }

    function fieldError(field: string) {
        return <InputError className="font-medium text-destructive" message={errors[field as keyof typeof errors]} />;
    }

    function numberField(field: 'prelim_grade' | 'midterm_grade' | 'final_grade', label: string, min: number, max: number) {
        return (
            <div className="grid gap-1.5">
                <Label htmlFor={field}>{label}</Label>
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
                <Label htmlFor={field}>{label}</Label>
                <Select
                    value={data[field]}
                    onValueChange={(value) => setData(field, value)}
                    required={required}
                    disabled={items.length === 0}
                >
                    <SelectTrigger id={field} className={selectClassName} aria-label={label}>
                        <SelectValue placeholder={items.length === 0 ? 'No options available' : emptyLabel} />
                    </SelectTrigger>
                    <SelectContent>
                        {items.map((item) => (
                            <SelectItem key={item.id} value={String(item.id)}>
                                {item.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {fieldError(field)}
                {field === 'section_id' && items.length === 0 && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                        <p>No sections have been created yet. Create one to assign this student.</p>
                        {!creatingSection ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="mt-2 border-amber-300 bg-white"
                                onClick={() => setCreatingSection(true)}
                            >
                                <Plus />
                                Create section
                            </Button>
                        ) : (
                            <div className="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="inline-section-name">Section name</Label>
                                    <Input
                                        id="inline-section-name"
                                        required
                                        maxLength={100}
                                        value={sectionData.name}
                                        onChange={(event) => setSectionData('name', event.target.value)}
                                        className="h-10 bg-background"
                                        autoFocus
                                    />
                                    {sectionErrors.name && <InputError message={sectionErrors.name} />}
                                </div>
                                <Button
                                    type="button"
                                    disabled={sectionProcessing || !sectionData.name.trim()}
                                    onClick={createSection}
                                    className="self-end bg-emerald-700 text-white hover:bg-emerald-800"
                                >
                                    {sectionProcessing ? 'Saving…' : 'Save section'}
                                </Button>
                            </div>
                        )}
                    </div>
                )}
                {field === 'section_id' && data.section_id !== '' && (
                    <p className="flex items-center gap-1.5 text-xs text-emerald-700" role="status">
                        <CheckCircle2 className="size-3.5" />
                        Section selected
                    </p>
                )}
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
                className="bg-emerald-700 text-white hover:bg-emerald-800"
            >
                <Plus />
                Add record
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Add school data</DialogTitle>
                        <DialogDescription>Create a student, teacher, subject, section, or grade record.</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-5" role="group" aria-label="Record type">
                        {recordTypes.map((type) => (
                            <Button
                                key={type.id}
                                type="button"
                                onClick={() => chooseType(type.id)}
                                variant={recordType === type.id ? 'default' : 'outline'}
                                size="sm"
                                className={recordType === type.id ? 'bg-emerald-700 text-white hover:bg-emerald-800' : ''}
                                aria-pressed={recordType === type.id}
                            >
                                {type.label}
                            </Button>
                        ))}
                    </div>

                    <form onSubmit={submit} className="mt-1 grid gap-4">
                        {recordType === 'students' && (
                            <>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="student-name">
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
                                    <Label htmlFor="lrn">
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
                                    <Label htmlFor="grade_level">
                                        Grade level
                                    </Label>
                                    <select
                                        id="grade_level"
                                        required
                                        value={data.grade_level}
                                        onChange={(event) => setData('grade_level', event.target.value)}
                                        className={selectClassName}
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
                                <Label htmlFor="teacher-name">
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
                                    <Label htmlFor="subject-name">
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
                                    <Label htmlFor="subject-code">
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
                                <Label htmlFor="section-name">
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
                                <p className="text-sm leading-5 text-muted-foreground">
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
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing || (recordType === 'students' && options.sections.length === 0)}
                                className="bg-emerald-700 text-white hover:bg-emerald-800"
                            >
                                {processing
                                    ? 'Saving…'
                                    : recordType === 'students' && options.sections.length === 0
                                      ? 'Create a section first'
                                      : `Create ${title.toLowerCase()}`}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
