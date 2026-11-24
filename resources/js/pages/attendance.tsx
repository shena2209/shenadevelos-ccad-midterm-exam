import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarCheck, CheckCircle2, Eye } from 'lucide-react';
import { useState } from 'react';

type AttendanceStatus = 'Present' | 'Absent' | 'Late' | 'Excused';

interface AttendanceStudent {
    id: number;
    name: string;
    lrn: string;
    grade_level: string;
    section_name: string;
    status: AttendanceStatus | null;
    notes: string | null;
}

interface AttendanceStats {
    totalStudents: number;
    marked: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    unmarked: number;
    attendanceRate: number;
}

interface AttendanceHistoryRecord {
    date: string;
    marked: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    students: AttendanceHistoryStudent[];
}

interface AttendanceHistoryStudent {
    id: number;
    name: string;
    lrn: string;
    grade_level: string;
    section_name: string;
    status: AttendanceStatus;
    notes: string | null;
}

type AttendanceEntry = {
    [key: string]: FormDataConvertible;
    student_id: number;
    status: AttendanceStatus | '';
    notes: string;
};

interface AttendanceForm {
    [key: string]: FormDataConvertible;
    date: string;
    records: AttendanceEntry[];
}

interface AttendanceProps {
    date: string;
    students: AttendanceStudent[];
    statuses: AttendanceStatus[];
    history: AttendanceHistoryRecord[];
    stats: AttendanceStats;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
];

export default function Attendance({ date, students, statuses, history, stats }: AttendanceProps) {
    const [saveMessage, setSaveMessage] = useState('');
    const [selectedHistoryRecord, setSelectedHistoryRecord] = useState<AttendanceHistoryRecord | null>(null);
    const { data, setData, post, processing, errors } = useForm<AttendanceForm>({
        date,
        records: students.map((student) => ({
            student_id: student.id,
            status: student.status ?? '',
            notes: student.notes ?? '',
        })),
    });

    const unmarkedCount = data.records.filter((record) => record.status === '').length;

    function updateRecord(index: number, changes: Partial<AttendanceEntry>) {
        setData(
            'records',
            data.records.map((record, recordIndex) => (recordIndex === index ? { ...record, ...changes } : record)),
        );
        setSaveMessage('');
    }

    function markAllPresent() {
        setData(
            'records',
            data.records.map((record) => ({ ...record, status: 'Present' as const })),
        );
        setSaveMessage('');
    }

    function saveAttendance() {
        post(route('attendance.store'), {
            preserveScroll: true,
            onSuccess: () => setSaveMessage('Attendance saved successfully.'),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />
            <main className="bg-muted/30 flex flex-1 flex-col gap-6 p-4 md:gap-8 md:p-8">
                <header className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                    <div className="space-y-2">
                        <Badge variant="secondary" className="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800 hover:bg-emerald-100">
                            <CalendarCheck className="mr-2 size-3.5" />
                            Daily roll call
                        </Badge>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">Student attendance</h1>
                        <p className="text-muted-foreground max-w-2xl text-sm md:text-base">
                            Record each student&apos;s daily status and review attendance for the selected date.
                        </p>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="attendance-date">Attendance date</Label>
                        <Input
                            id="attendance-date"
                            type="date"
                            value={date}
                            onChange={(event) => {
                                if (event.target.value) {
                                    router.get(route('attendance.index'), { date: event.target.value }, { preserveScroll: true });
                                }
                            }}
                            className="w-full sm:w-48"
                        />
                    </div>
                </header>

                {saveMessage && (
                    <Alert className="border-emerald-200 bg-emerald-50 text-emerald-950">
                        <CheckCircle2 className="size-4 text-emerald-700" />
                        <AlertTitle>Saved successfully</AlertTitle>
                        <AlertDescription>{saveMessage}</AlertDescription>
                    </Alert>
                )}

                <section aria-label="Attendance summary" className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total students" value={stats.totalStudents} />
                    <StatCard label="Attendance rate" value={`${stats.attendanceRate}%`} detail="Present + late" />
                    <StatCard label="Present" value={stats.present} />
                    <StatCard label="Absent" value={stats.absent} />
                    <StatCard label="Late" value={stats.late} />
                    <StatCard label="Excused" value={stats.excused} />
                    <StatCard label="Unmarked" value={stats.unmarked} />
                    <StatCard label="Marked" value={`${stats.marked} / ${stats.totalStudents}`} />
                </section>

                <Card className="overflow-hidden shadow-sm">
                    <CardHeader className="gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-lg">Daily student list</CardTitle>
                            <CardDescription>Attendance rate counts Present and Late students divided by all enrolled students.</CardDescription>
                        </div>
                        <Button type="button" variant="outline" onClick={markAllPresent} disabled={students.length === 0 || processing}>
                            Mark all present
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        {students.length > 0 ? (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[760px] text-left text-sm">
                                        <thead className="bg-muted/40 text-muted-foreground text-xs font-medium">
                                            <tr>
                                                <th scope="col" className="px-6 py-3.5">
                                                    Student
                                                </th>
                                                <th scope="col" className="px-6 py-3.5">
                                                    Class
                                                </th>
                                                <th scope="col" className="px-6 py-3.5">
                                                    Status
                                                </th>
                                                <th scope="col" className="px-6 py-3.5">
                                                    Note
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {students.map((student, index) => (
                                                <tr key={student.id} className="hover:bg-muted/30 transition-colors">
                                                    <td className="px-6 py-4">
                                                        <p className="font-medium">{student.name}</p>
                                                        <p className="text-muted-foreground mt-0.5 text-xs">LRN {student.lrn}</p>
                                                    </td>
                                                    <td className="text-muted-foreground px-6 py-4">
                                                        {student.grade_level} · {student.section_name}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <select
                                                            required
                                                            aria-label={`Attendance status for ${student.name}`}
                                                            value={data.records[index]?.status ?? ''}
                                                            onChange={(event) =>
                                                                updateRecord(index, {
                                                                    status: event.target.value as AttendanceStatus | '',
                                                                })
                                                            }
                                                            className="border-input bg-background text-foreground h-10 w-full rounded-md border px-3 py-2 text-sm sm:max-w-40"
                                                        >
                                                            <option value="">Unmarked</option>
                                                            {statuses.map((status) => (
                                                                <option key={status} value={status}>
                                                                    {status}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <InputError message={errors[`records.${index}.status`]} />
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <Input
                                                            value={data.records[index]?.notes ?? ''}
                                                            maxLength={255}
                                                            aria-label={`Attendance note for ${student.name}`}
                                                            placeholder="Optional"
                                                            onChange={(event) => updateRecord(index, { notes: event.target.value })}
                                                        />
                                                        <InputError message={errors[`records.${index}.notes`]} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="border-t p-4">
                                    {errors.records && <p className="text-destructive mb-3 text-sm">{errors.records}</p>}
                                    {unmarkedCount > 0 && (
                                        <p className="text-muted-foreground mb-3 text-sm">
                                            Choose a status for all {unmarkedCount} unmarked {unmarkedCount === 1 ? 'student' : 'students'} before
                                            saving.
                                        </p>
                                    )}
                                    <div className="flex justify-end">
                                        <Button type="button" onClick={saveAttendance} disabled={processing || unmarkedCount > 0}>
                                            {processing ? 'Saving…' : 'Save attendance'}
                                        </Button>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="p-8 text-center">
                                <p className="font-medium">No students to mark</p>
                                <p className="text-muted-foreground mt-1 text-sm">Add students and assign them to a section first.</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-lg">Saved attendance records</CardTitle>
                        <CardDescription>Attendance is saved by date. Select a saved day to reopen its student statuses and notes.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {history.length > 0 ? (
                            <div className="divide-y overflow-hidden rounded-md border">
                                {history.map((record) => (
                                    <div key={record.date} className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium">{record.date}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {record.marked} marked · {record.present} present · {record.absent} absent · {record.late} late ·{' '}
                                                {record.excused} excused
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            aria-label={`View records for ${record.date}`}
                                            onClick={() => setSelectedHistoryRecord(record)}
                                        >
                                            <Eye className="mr-2 size-4" />
                                            View records
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">No attendance records have been saved yet.</p>
                        )}
                        {history.length === 20 && (
                            <p className="text-muted-foreground mt-3 text-xs">
                                Showing the 20 most recent saved dates. Use the date picker above to look up an older date.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Dialog open={selectedHistoryRecord !== null} onOpenChange={(open) => !open && setSelectedHistoryRecord(null)}>
                    <DialogContent className="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>Attendance records</DialogTitle>
                            <DialogDescription>
                                {selectedHistoryRecord
                                    ? `Saved student attendance for ${selectedHistoryRecord.date}.`
                                    : 'Saved student attendance details.'}
                            </DialogDescription>
                        </DialogHeader>
                        {selectedHistoryRecord && (
                            <div className="max-h-[60vh] overflow-auto rounded-md border">
                                <table className="w-full min-w-[640px] text-left text-sm">
                                    <thead className="bg-muted/40 text-muted-foreground sticky top-0 text-xs font-medium">
                                        <tr>
                                            <th scope="col" className="px-4 py-3">
                                                Student
                                            </th>
                                            <th scope="col" className="px-4 py-3">
                                                Class
                                            </th>
                                            <th scope="col" className="px-4 py-3">
                                                Status
                                            </th>
                                            <th scope="col" className="px-4 py-3">
                                                Note
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {selectedHistoryRecord.students.map((student) => (
                                            <tr key={student.id}>
                                                <td className="px-4 py-3">
                                                    <p className="font-medium">{student.name}</p>
                                                    <p className="text-muted-foreground mt-0.5 text-xs">LRN {student.lrn}</p>
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {student.grade_level} · {student.section_name}
                                                </td>
                                                <td className="px-4 py-3">{student.status}</td>
                                                <td className="text-muted-foreground px-4 py-3">{student.notes || '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </main>
        </AppLayout>
    );
}

function StatCard({ label, value, detail }: { label: string; value: string | number; detail?: string }) {
    return (
        <Card className="shadow-sm">
            <CardContent className="p-4">
                <p className="text-muted-foreground text-sm">{label}</p>
                <p className="mt-2 text-2xl font-semibold tabular-nums">{value}</p>
                {detail && <p className="text-muted-foreground mt-1 text-xs">{detail}</p>}
            </CardContent>
        </Card>
    );
}
