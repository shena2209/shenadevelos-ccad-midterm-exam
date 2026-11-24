import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Eye, Pencil, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

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
    subjects?: { id: number; name: string; code: string }[];
    students?: { id: number; name: string; lrn: string; grade_level: string }[];
}

interface SectionOption {
    id: number;
    name: string;
}

interface SchoolRecordsProps {
    title: 'Students' | 'Teachers' | 'Subjects' | 'Sections';
    records: SchoolRecord[];
    sections?: SectionOption[];
    teachers?: SectionOption[];
}

const singularLabels: Record<SchoolRecordsProps['title'], string> = {
    Students: 'student',
    Teachers: 'teacher',
    Subjects: 'subject',
    Sections: 'section',
};

const deleteDescriptions: Record<SchoolRecordsProps['title'], string> = {
    Students: 'This student can only be deleted if they have no linked grade or attendance records.',
    Teachers: 'This teacher can only be deleted if no subjects are assigned to them.',
    Subjects: 'This subject can only be deleted if it has no linked grade records.',
    Sections: 'This section can only be deleted if no students are enrolled in it.',
};

export default function SchoolRecords({ title, records, sections = [], teachers = [] }: SchoolRecordsProps) {
    const [search, setSearch] = useState('');
    const [selectedRecord, setSelectedRecord] = useState<SchoolRecord | null>(null);
    const [editingRecord, setEditingRecord] = useState<SchoolRecord | null>(null);
    const [deletingRecord, setDeletingRecord] = useState<SchoolRecord | null>(null);
    const editForm = useForm({
        name: '',
        lrn: '',
        grade_level: '',
        section_id: '',
        subject_name: '',
        subject_code: '',
        teacher_id: '',
    });
    const deleteForm = useForm({ record: '', student: '' });
    const singularLabel = singularLabels[title];
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: `/school-records/${title.toLowerCase()}` },
    ];
    const filteredRecords = useMemo(() => {
        const query = search.trim().toLowerCase();

        return query ? records.filter((record) => `${record.name} ${record.detail}`.toLowerCase().includes(query)) : records;
    }, [records, search]);

    function recordRoutePrefix() {
        return title === 'Students' ? 'dashboard.records.students' : `school-records.${title.toLowerCase()}`;
    }

    function editRecord(record: SchoolRecord) {
        setSelectedRecord(null);
        editForm.clearErrors();
        editForm.setData({
            name: record.name,
            lrn: record.lrn ?? '',
            grade_level: record.grade_level ?? '',
            section_id: String(record.section_id ?? ''),
            subject_name: title === 'Subjects' ? record.name : '',
            subject_code: title === 'Subjects' ? (record.code ?? '') : '',
            teacher_id: String(record.teacher_id ?? ''),
        });
        setEditingRecord(record);
    }

    function submitEdit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!editingRecord) return;

        editForm.patch(route(`${recordRoutePrefix()}.update`, editingRecord.id), {
            preserveScroll: true,
            onSuccess: () => setEditingRecord(null),
        });
    }

    function deleteRecord() {
        if (!deletingRecord) return;

        deleteForm.delete(route(`${recordRoutePrefix()}.destroy`, deletingRecord.id), {
            preserveScroll: true,
            onSuccess: () => setDeletingRecord(null),
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
                        <CardDescription>Select a record to view its details.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {filteredRecords.length > 0 ? (
                            <div className="divide-y">
                                {filteredRecords.map((record) => (
                                    <div
                                        key={record.id}
                                        className="hover:bg-muted/30 flex items-center justify-between gap-4 px-6 py-4 transition-colors"
                                    >
                                        <button
                                            type="button"
                                            aria-label={`View ${singularLabel} record for ${record.name}`}
                                            onClick={() => setSelectedRecord(record)}
                                            className="focus-visible:ring-ring min-w-0 flex-1 text-left focus-visible:ring-2 focus-visible:outline-hidden"
                                        >
                                            <p className="truncate font-medium">{record.name}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">{record.detail}</p>
                                        </button>
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`View ${singularLabel} ${record.name}`}
                                                onClick={() => setSelectedRecord(record)}
                                            >
                                                <Eye />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Edit ${singularLabel} ${record.name}`}
                                                onClick={() => editRecord(record)}
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Delete ${singularLabel} ${record.name}`}
                                                onClick={() => {
                                                    deleteForm.clearErrors();
                                                    setDeletingRecord(record);
                                                }}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground p-8 text-center text-sm">
                                {records.length === 0 ? `No ${title.toLowerCase()} records yet.` : 'No matching records.'}
                            </p>
                        )}
                    </CardContent>
                </Card>
                <Dialog open={selectedRecord !== null} onOpenChange={(open) => !open && setSelectedRecord(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{singularLabel} details</DialogTitle>
                            <DialogDescription>Record information from your school data.</DialogDescription>
                        </DialogHeader>
                        {selectedRecord && (
                            <div className="rounded-lg border p-4">
                                <p className="font-medium">{selectedRecord.name}</p>
                                <p className="text-muted-foreground mt-1 text-sm">{selectedRecord.detail}</p>
                                {title === 'Students' && (
                                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt className="text-muted-foreground">LRN</dt>
                                            <dd className="font-medium">{selectedRecord.lrn}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">Grade level</dt>
                                            <dd className="font-medium">{selectedRecord.grade_level}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">Section</dt>
                                            <dd className="font-medium">{selectedRecord.section_name}</dd>
                                        </div>
                                    </dl>
                                )}
                                {title === 'Teachers' && (
                                    <div className="mt-4">
                                        <p className="text-muted-foreground text-sm">Assigned subjects</p>
                                        {selectedRecord.subjects?.length ? (
                                            <ul className="mt-2 space-y-2 text-sm">
                                                {selectedRecord.subjects.map((subject) => (
                                                    <li key={subject.id} className="bg-muted/40 rounded-md px-3 py-2">
                                                        <span className="font-medium">{subject.name}</span>
                                                        <span className="text-muted-foreground"> · {subject.code}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="text-muted-foreground mt-2 text-sm">No subjects assigned.</p>
                                        )}
                                    </div>
                                )}
                                {title === 'Subjects' && (
                                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt className="text-muted-foreground">Subject code</dt>
                                            <dd className="font-medium">{selectedRecord.code}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">Assigned teacher</dt>
                                            <dd className="font-medium">{selectedRecord.teacher_name ?? 'No teacher assigned'}</dd>
                                        </div>
                                    </dl>
                                )}
                                {title === 'Sections' && (
                                    <div className="mt-4">
                                        <p className="text-muted-foreground text-sm">Enrolled students</p>
                                        {selectedRecord.students?.length ? (
                                            <ul className="mt-2 max-h-60 space-y-2 overflow-y-auto text-sm">
                                                {selectedRecord.students.map((student) => (
                                                    <li key={student.id} className="bg-muted/40 rounded-md px-3 py-2">
                                                        <p className="font-medium">{student.name}</p>
                                                        <p className="text-muted-foreground mt-1">
                                                            LRN {student.lrn} · {student.grade_level}
                                                        </p>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="text-muted-foreground mt-2 text-sm">No students are enrolled in this section.</p>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        <DialogFooter>
                            {selectedRecord && (
                                <Button
                                    type="button"
                                    onClick={() => editRecord(selectedRecord)}
                                    className="bg-emerald-700 text-white hover:bg-emerald-800"
                                >
                                    <Pencil />
                                    Edit
                                </Button>
                            )}
                            <Button type="button" variant="outline" onClick={() => setSelectedRecord(null)}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                <Dialog open={editingRecord !== null} onOpenChange={(open) => !open && setEditingRecord(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit {singularLabel}</DialogTitle>
                            <DialogDescription>Update this {singularLabel} record.</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={submitEdit} className="grid gap-4">
                            {title === 'Students' && (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-student-name">Full name</Label>
                                        <Input
                                            id="edit-student-name"
                                            required
                                            maxLength={150}
                                            value={editForm.data.subject_name}
                                            onChange={(event) => editForm.setData('subject_name', event.target.value)}
                                        />
                                        <InputError message={editForm.errors.subject_name} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-student-lrn">Learner reference number (LRN)</Label>
                                        <Input
                                            id="edit-student-lrn"
                                            required
                                            inputMode="numeric"
                                            maxLength={12}
                                            pattern="[0-9]{12}"
                                            value={editForm.data.lrn}
                                            onChange={(event) => editForm.setData('lrn', event.target.value.replace(/\D/g, '').slice(0, 12))}
                                        />
                                        <InputError message={editForm.errors.lrn} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-student-grade">Grade level</Label>
                                        <select
                                            id="edit-student-grade"
                                            required
                                            value={editForm.data.grade_level}
                                            onChange={(event) => editForm.setData('grade_level', event.target.value)}
                                            className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                        >
                                            {Array.from({ length: 12 }, (_, index) => `Grade ${index + 1}`).map((grade) => (
                                                <option key={grade} value={grade}>
                                                    {grade}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={editForm.errors.grade_level} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-student-section">Section</Label>
                                        <select
                                            id="edit-student-section"
                                            required
                                            value={editForm.data.section_id}
                                            onChange={(event) => editForm.setData('section_id', event.target.value)}
                                            className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                        >
                                            <option value="">Select a section</option>
                                            {sections.map((section) => (
                                                <option key={section.id} value={section.id}>
                                                    {section.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={editForm.errors.section_id} />
                                    </div>
                                </>
                            )}
                            {title === 'Teachers' && (
                                <div className="grid gap-1.5">
                                    <Label htmlFor="edit-teacher-name">Full name</Label>
                                    <Input
                                        id="edit-teacher-name"
                                        required
                                        maxLength={150}
                                        value={editForm.data.name}
                                        onChange={(event) => editForm.setData('name', event.target.value)}
                                    />
                                    <InputError message={editForm.errors.name} />
                                </div>
                            )}
                            {title === 'Subjects' && (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-subject-name">Subject name</Label>
                                        <Input
                                            id="edit-subject-name"
                                            required
                                            maxLength={150}
                                            value={editForm.data.name}
                                            onChange={(event) => editForm.setData('name', event.target.value)}
                                        />
                                        <InputError message={editForm.errors.name} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-subject-code">Subject code</Label>
                                        <Input
                                            id="edit-subject-code"
                                            required
                                            maxLength={20}
                                            value={editForm.data.subject_code}
                                            onChange={(event) => editForm.setData('subject_code', event.target.value)}
                                        />
                                        <InputError message={editForm.errors.subject_code} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="edit-subject-teacher">Assigned teacher</Label>
                                        <select
                                            id="edit-subject-teacher"
                                            value={editForm.data.teacher_id}
                                            onChange={(event) => editForm.setData('teacher_id', event.target.value)}
                                            className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm"
                                        >
                                            <option value="">No teacher assigned</option>
                                            {teachers.map((teacher) => (
                                                <option key={teacher.id} value={teacher.id}>
                                                    {teacher.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={editForm.errors.teacher_id} />
                                    </div>
                                </>
                            )}
                            {title === 'Sections' && (
                                <div className="grid gap-1.5">
                                    <Label htmlFor="edit-section-name">Section name</Label>
                                    <Input
                                        id="edit-section-name"
                                        required
                                        maxLength={100}
                                        value={editForm.data.name}
                                        onChange={(event) => editForm.setData('name', event.target.value)}
                                    />
                                    <InputError message={editForm.errors.name} />
                                </div>
                            )}
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditingRecord(null)} disabled={editForm.processing}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={editForm.processing} className="bg-emerald-700 text-white hover:bg-emerald-800">
                                    {editForm.processing ? 'Saving…' : 'Save changes'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                <Dialog open={deletingRecord !== null} onOpenChange={(open) => !open && setDeletingRecord(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete {singularLabel}?</DialogTitle>
                            <DialogDescription>
                                Delete {deletingRecord?.name}. {deleteDescriptions[title]}
                            </DialogDescription>
                        </DialogHeader>
                        <InputError message={title === 'Students' ? deleteForm.errors.student : deleteForm.errors.record} />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDeletingRecord(null)} disabled={deleteForm.processing}>
                                Cancel
                            </Button>
                            <Button type="button" variant="destructive" onClick={deleteRecord} disabled={deleteForm.processing}>
                                {deleteForm.processing ? 'Deleting…' : `Delete ${singularLabel}`}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </main>
        </AppLayout>
    );
}
