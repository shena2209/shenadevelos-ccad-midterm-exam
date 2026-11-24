import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';

type RecordTitle = 'Students' | 'Teachers' | 'Subjects' | 'Sections';

interface EnrolledStudent {
    id: number;
    name: string;
    lrn: string;
    grade_level: string;
}

interface SchoolRecord {
    id: number;
    name: string;
    detail: string;
    lrn?: string;
    grade_level?: string;
    section_id?: number;
    section_name?: string;
    code?: string;
    teacher_id?: number | null;
    teacher_name?: string | null;
    subject_count?: number;
    student_count?: number;
    students?: EnrolledStudent[];
}

interface SchoolRecordsProps {
    title: RecordTitle;
    records: SchoolRecord[];
    options: {
        sections?: { id: number; name: string }[];
        teachers?: { id: number; name: string }[];
    };
}

const singularLabels: Record<RecordTitle, string> = {
    Students: 'student',
    Teachers: 'teacher',
    Subjects: 'subject',
    Sections: 'section',
};

const resources: Record<RecordTitle, string> = {
    Students: 'students',
    Teachers: 'teachers',
    Subjects: 'subjects',
    Sections: 'sections',
};

export default function SchoolRecords({ title, records, options }: SchoolRecordsProps) {
    const [search, setSearch] = useState('');
    const [selectedRecord, setSelectedRecord] = useState<SchoolRecord | null>(null);
    const [editing, setEditing] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const singularLabel = singularLabels[title];
    const resource = resources[title];
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: `/school-records/${resource}` },
    ];
    const filteredRecords = useMemo(() => {
        const query = search.trim().toLowerCase();

        return query ? records.filter((record) => `${record.name} ${record.detail}`.toLowerCase().includes(query)) : records;
    }, [records, search]);
    const { data, setData, patch, processing, errors, clearErrors } = useForm({
        name: '',
        lrn: '',
        grade_level: '',
        section_id: '',
        code: '',
        teacher_id: '',
    });
    const { delete: destroy, processing: deleting, errors: deleteErrors } = useForm<{ record: string }>({ record: '' });

    function selectRecord(record: SchoolRecord) {
        setSelectedRecord(record);
        setEditing(false);
        setConfirmingDelete(false);
        setData({
            name: record.name,
            lrn: record.lrn ?? '',
            grade_level: record.grade_level ?? '',
            section_id: record.section_id ? String(record.section_id) : '',
            code: record.code ?? '',
            teacher_id: record.teacher_id ? String(record.teacher_id) : '',
        });
        clearErrors();
    }

    function closeDetails(open: boolean) {
        if (open) return;

        setSelectedRecord(null);
        setEditing(false);
        setConfirmingDelete(false);
        clearErrors();
    }

    function submitEdit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!selectedRecord) return;

        patch(route(`school-records.${resource}.update`, selectedRecord.id), {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedRecord(null);
                setEditing(false);
            },
        });
    }

    function deleteRecord() {
        if (!selectedRecord) return;

        destroy(route(`school-records.${resource}.destroy`, selectedRecord.id), {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedRecord(null);
                setConfirmingDelete(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <main className="bg-muted/30 flex flex-1 flex-col gap-6 p-4 md:gap-8 md:p-8">
                <header className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div className="space-y-2">
                        <p className="text-muted-foreground text-sm">School records</p>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">{title}</h1>
                        <p className="text-muted-foreground text-sm">
                            {records.length} {singularLabel} {records.length === 1 ? 'record' : 'records'}.
                        </p>
                    </div>
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={`Search ${title.toLowerCase()}`}
                            aria-label={`Search ${title.toLowerCase()}`}
                            className="pl-9"
                        />
                    </div>
                </header>

                <Card className="overflow-hidden shadow-sm">
                    <CardHeader className="border-b">
                        <CardTitle className="text-lg">{singularLabel} records</CardTitle>
                        <CardDescription>Select a record to view, edit, or delete it.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {filteredRecords.length > 0 ? (
                            <div className="divide-y">
                                {filteredRecords.map((record) => (
                                    <button
                                        key={record.id}
                                        type="button"
                                        aria-label={`View ${singularLabel} record for ${record.name}`}
                                        onClick={() => selectRecord(record)}
                                        className="hover:bg-muted/30 focus-visible:ring-ring flex w-full items-center justify-between gap-4 px-6 py-4 text-left transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">{record.name}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">{record.detail}</p>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground p-8 text-center text-sm">
                                {records.length === 0 ? `No ${title.toLowerCase()} records yet.` : 'No matching records.'}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Dialog open={selectedRecord !== null} onOpenChange={closeDetails}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>{editing ? `Edit ${singularLabel}` : `${singularLabel} details`}</DialogTitle>
                            <DialogDescription>
                                {editing ? `Update this ${singularLabel} record.` : 'Record information from your school data.'}
                            </DialogDescription>
                        </DialogHeader>

                        {selectedRecord && editing && (
                            <form onSubmit={submitEdit} className="grid gap-4">
                                {(title === 'Students' || title === 'Teachers') && (
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="record-name">Full name</Label>
                                        <Input
                                            id="record-name"
                                            required
                                            maxLength={150}
                                            value={data.name}
                                            onChange={(event) => setData('name', event.target.value)}
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                )}

                                {title === 'Students' && (
                                    <>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-lrn">Learner reference number (LRN)</Label>
                                            <Input
                                                id="record-lrn"
                                                required
                                                inputMode="numeric"
                                                maxLength={12}
                                                pattern="[0-9]{12}"
                                                value={data.lrn}
                                                onChange={(event) => setData('lrn', event.target.value.replace(/\D/g, '').slice(0, 12))}
                                            />
                                            <InputError message={errors.lrn} />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-grade-level">Grade level</Label>
                                            <select
                                                id="record-grade-level"
                                                required
                                                value={data.grade_level}
                                                onChange={(event) => setData('grade_level', event.target.value)}
                                                className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                            >
                                                <option value="">Select a grade level</option>
                                                {Array.from({ length: 12 }, (_, index) => `Grade ${index + 1}`).map((grade) => (
                                                    <option key={grade} value={grade}>
                                                        {grade}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.grade_level} />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-section">Section</Label>
                                            <select
                                                id="record-section"
                                                required
                                                value={data.section_id}
                                                onChange={(event) => setData('section_id', event.target.value)}
                                                className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                            >
                                                <option value="">Select a section</option>
                                                {options.sections?.map((section) => (
                                                    <option key={section.id} value={section.id}>
                                                        {section.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.section_id} />
                                        </div>
                                    </>
                                )}

                                {title === 'Subjects' && (
                                    <>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-subject-name">Subject name</Label>
                                            <Input
                                                id="record-subject-name"
                                                required
                                                maxLength={150}
                                                value={data.name}
                                                onChange={(event) => setData('name', event.target.value)}
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-subject-code">Subject code</Label>
                                            <Input
                                                id="record-subject-code"
                                                required
                                                maxLength={20}
                                                value={data.code}
                                                onChange={(event) => setData('code', event.target.value)}
                                            />
                                            <InputError message={errors.code} />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="record-teacher">Assigned teacher</Label>
                                            <select
                                                id="record-teacher"
                                                value={data.teacher_id}
                                                onChange={(event) => setData('teacher_id', event.target.value)}
                                                className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                            >
                                                <option value="">No teacher assigned</option>
                                                {options.teachers?.map((teacher) => (
                                                    <option key={teacher.id} value={teacher.id}>
                                                        {teacher.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.teacher_id} />
                                        </div>
                                    </>
                                )}

                                {title === 'Sections' && (
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="record-section-name">Section name</Label>
                                        <Input
                                            id="record-section-name"
                                            required
                                            maxLength={100}
                                            value={data.name}
                                            onChange={(event) => setData('name', event.target.value)}
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                )}

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setEditing(false)} disabled={processing}>
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing} className="bg-emerald-700 text-white hover:bg-emerald-800">
                                        {processing ? 'Saving…' : 'Save changes'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        )}

                        {selectedRecord && !editing && (
                            <>
                                <div className="rounded-lg border p-4">
                                    <p className="font-medium">{selectedRecord.name}</p>
                                    <dl className="mt-3 grid gap-2 text-sm">
                                        {title === 'Students' && (
                                            <>
                                                <Detail label="LRN" value={selectedRecord.lrn} />
                                                <Detail label="Grade level" value={selectedRecord.grade_level} />
                                                <Detail label="Section" value={selectedRecord.section_name} />
                                            </>
                                        )}
                                        {title === 'Teachers' && (
                                            <Detail label="Assigned subjects" value={selectedRecord.subject_count ?? 0} />
                                        )}
                                        {title === 'Subjects' && (
                                            <>
                                                <Detail label="Subject code" value={selectedRecord.code} />
                                                <Detail label="Assigned teacher" value={selectedRecord.teacher_name ?? 'No teacher assigned'} />
                                            </>
                                        )}
                                        {title === 'Sections' && (
                                            <>
                                                <Detail label="Enrolled students" value={selectedRecord.student_count ?? 0} />
                                                {selectedRecord.students && selectedRecord.students.length > 0 ? (
                                                    <ul className="mt-3 divide-y border-t" aria-label="Students enrolled in this section">
                                                        {selectedRecord.students.map((student) => (
                                                            <li key={student.id} className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                                <span className="font-medium">{student.name}</span>
                                                                <span className="text-muted-foreground text-xs">
                                                                    LRN {student.lrn} · {student.grade_level}
                                                                </span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : (
                                                    <p className="text-muted-foreground mt-3 border-t pt-3 text-sm">
                                                        No students are enrolled in this section.
                                                    </p>
                                                )}
                                            </>
                                        )}
                                    </dl>
                                </div>

                                {confirmingDelete ? (
                                    <div className="grid gap-3 rounded-lg border border-destructive/40 bg-destructive/5 p-4">
                                        <div>
                                            <p className="font-medium">Delete this {singularLabel}?</p>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                This action cannot be undone. Records with linked data cannot be deleted.
                                            </p>
                                        </div>
                                        {deleteErrors.record && <InputError message={deleteErrors.record} />}
                                        <div className="flex justify-end gap-2">
                                            <Button type="button" variant="outline" onClick={() => setConfirmingDelete(false)} disabled={deleting}>
                                                Cancel
                                            </Button>
                                            <Button type="button" variant="destructive" onClick={deleteRecord} disabled={deleting}>
                                                {deleting ? 'Deleting…' : 'Delete record'}
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <>
                                        {deleteErrors.record && <InputError message={deleteErrors.record} />}
                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => closeDetails(false)}>
                                                Close
                                            </Button>
                                            <Button type="button" variant="outline" onClick={() => setEditing(true)}>
                                                <Pencil />
                                                Edit
                                            </Button>
                                            <Button type="button" variant="destructive" onClick={() => setConfirmingDelete(true)}>
                                                <Trash2 />
                                                Delete
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </main>
        </AppLayout>
    );
}

function Detail({ label, value }: { label: string; value?: string | number | null }) {
    return (
        <div className="grid grid-cols-[minmax(8rem,auto)_1fr] gap-3">
            <dt className="text-muted-foreground">{label}</dt>
            <dd>{value ?? '—'}</dd>
        </div>
    );
}