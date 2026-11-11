import CreateRecordDialog from '@/components/dashboard/create-record-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    ArrowDownToLine,
    ArrowUpRight,
    BookOpen,
    Check,
    CheckCircle2,
    GraduationCap,
    Layers3,
    Search,
    UsersRound,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

interface DashboardStats {
    students: number;
    teachers: number;
    subjects: number;
    sections: number;
    grades: number;
    passed: number;
    failed: number;
    pending: number;
    passRate: number;
}

interface RecentGrade {
    id: number;
    student_name: string;
    lrn: string;
    subject_name: string;
    subject_code: string;
    grade_level: string;
    section_name: string;
    average_grade: number | string | null;
    pass_fail_status: 'Passed' | 'Failed' | null;
}

interface SubjectPerformance {
    name: string;
    code: string;
    average_grade: number | string | null;
    grade_count: number;
}

interface DashboardOptions {
    students: { id: number; name: string; lrn: string; grade_level: string; section_name: string }[];
    teachers: { id: number; name: string }[];
    subjects: { id: number; name: string; code: string; teacher_id: number | null; teacher_name: string | null }[];
    sections: { id: number; name: string }[];
}

interface DashboardProps {
    stats: DashboardStats;
    recentGrades: RecentGrade[];
    subjectPerformance: SubjectPerformance[];
    options: DashboardOptions;
}

const numberFormatter = new Intl.NumberFormat();
const recordLabels: Record<'students' | 'teachers' | 'subjects' | 'sections' | 'grades', string> = {
    students: 'Student',
    teachers: 'Teacher',
    subjects: 'Subject',
    sections: 'Section',
    grades: 'Grade',
};

function formatGrade(grade: number | string | null): string {
    return grade === null ? '—' : Number(grade).toFixed(2);
}

function csvCell(value: string | number): string {
    return `"${String(value).replace(/"/g, '""')}"`;
}

export default function Dashboard({ stats, recentGrades, subjectPerformance, options }: DashboardProps) {
    const [search, setSearch] = useState('');
    const [successMessage, setSuccessMessage] = useState('');
    const filteredGrades = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) return recentGrades;

        return recentGrades.filter((grade) =>
            [grade.student_name, grade.lrn, grade.subject_name, grade.subject_code, grade.section_name].join(' ').toLowerCase().includes(query),
        );
    }, [recentGrades, search]);

    const summaryCards = [
        { label: 'Students enrolled', value: stats.students, detail: 'Learner records', icon: UsersRound, accent: 'bg-sky-50 text-sky-700' },
        { label: 'Teaching staff', value: stats.teachers, detail: 'Active teachers', icon: GraduationCap, accent: 'bg-violet-50 text-violet-700' },
        { label: 'Subjects', value: stats.subjects, detail: 'In the curriculum', icon: BookOpen, accent: 'bg-amber-50 text-amber-700' },
        { label: 'Sections', value: stats.sections, detail: 'Class groups', icon: Layers3, accent: 'bg-emerald-50 text-emerald-700' },
    ];

    function exportGrades() {
        const rows = [
            ['Student', 'LRN', 'Subject', 'Subject code', 'Section', 'Average grade', 'Status'],
            ...filteredGrades.map((grade) => [
                grade.student_name,
                grade.lrn,
                grade.subject_name,
                grade.subject_code,
                grade.section_name,
                formatGrade(grade.average_grade),
                grade.pass_fail_status ?? 'Pending',
            ]),
        ];
        const csv = rows.map((row) => row.map(csvCell).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        const link = document.createElement('a');

        link.href = url;
        link.download = 'grade-records.csv';
        link.click();
        URL.revokeObjectURL(url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <main className="flex flex-1 flex-col gap-6 bg-muted/30 p-4 md:gap-8 md:p-8">
                <header className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                    <div className="space-y-2">
                        <Badge variant="secondary" className="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800 hover:bg-emerald-100">
                            <span className="mr-2 size-1.5 rounded-full bg-emerald-600" />
                            School year overview
                        </Badge>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">Academic dashboard</h1>
                        <p className="max-w-2xl text-sm text-muted-foreground md:text-base">
                            A clear view of enrollment, grade outcomes, and the latest classroom activity.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" onClick={exportGrades} disabled={filteredGrades.length === 0}>
                            <ArrowDownToLine />
                            Export records
                        </Button>
                        <CreateRecordDialog
                            options={options}
                            onCreated={(type) => setSuccessMessage(`${recordLabels[type]} created successfully.`)}
                        />
                    </div>
                </header>

                {successMessage && (
                    <Alert className="border-emerald-200 bg-emerald-50 text-emerald-950">
                        <CheckCircle2 className="size-4 text-emerald-700" />
                        <AlertTitle>Saved successfully</AlertTitle>
                        <AlertDescription>{successMessage}</AlertDescription>
                    </Alert>
                )}

                <section aria-label="School statistics" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {summaryCards.map(({ label, value, detail, icon: Icon, accent }) => (
                        <Card key={label} className="overflow-hidden border-border/70 shadow-sm">
                            <CardContent className="flex items-start justify-between p-5">
                                <div className="space-y-3">
                                    <p className="text-sm font-medium text-muted-foreground">{label}</p>
                                    <div>
                                        <p className="text-3xl font-semibold tracking-tight tabular-nums">{numberFormatter.format(value)}</p>
                                        <p className="mt-1 text-xs text-muted-foreground">{detail}</p>
                                    </div>
                                </div>
                                <span className={`flex size-11 items-center justify-center rounded-xl ${accent}`}>
                                    <Icon className="size-5" aria-hidden="true" />
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(300px,1fr)]">
                    <Card id="performance" className="shadow-sm">
                        <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0">
                            <div className="space-y-1">
                                <CardTitle className="text-lg">Subject performance</CardTitle>
                                <CardDescription>Average grade across recorded assessments</CardDescription>
                            </div>
                            <Badge variant="outline" className="shrink-0 rounded-full font-normal">
                                Top 5 subjects
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            {subjectPerformance.length > 0 ? (
                                <div className="space-y-5">
                                    {subjectPerformance.map((subject) => {
                                        const score = Number(subject.average_grade ?? 0);
                                        const width = Math.min(100, Math.max(0, score));

                                        return (
                                            <div key={subject.code} className="space-y-2">
                                                <div className="flex items-center justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">{subject.name}</p>
                                                        <p className="text-xs text-muted-foreground">{subject.code}</p>
                                                    </div>
                                                    <p className="shrink-0 text-sm font-semibold tabular-nums">{formatGrade(subject.average_grade)}</p>
                                                </div>
                                                <div
                                                    className="h-2 overflow-hidden rounded-full bg-muted"
                                                    role="progressbar"
                                                    aria-label={`${subject.name} average`}
                                                    aria-valuemin={0}
                                                    aria-valuemax={100}
                                                    aria-valuenow={width}
                                                >
                                                    <div className="h-full rounded-full bg-emerald-600 transition-all" style={{ width: `${width}%` }} />
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {numberFormatter.format(subject.grade_count)} {subject.grade_count === 1 ? 'grade record' : 'grade records'}
                                                </p>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed p-8 text-center">
                                    <BookOpen className="mx-auto size-8 text-muted-foreground/60" />
                                    <p className="mt-3 text-sm font-medium">No subject data yet</p>
                                    <p className="mt-1 text-sm text-muted-foreground">Subject averages will appear here after grades are recorded.</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader className="space-y-1">
                            <CardTitle className="text-lg">Grade summary</CardTitle>
                            <CardDescription>Outcome of completed assessments</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-xl bg-emerald-50 p-5">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-emerald-900">Passing rate</p>
                                        <p className="mt-2 text-4xl font-semibold tracking-tight text-emerald-950 tabular-nums">{stats.passRate}%</p>
                                    </div>
                                    <CheckCircle2 className="size-6 text-emerald-700" aria-hidden="true" />
                                </div>
                                <div
                                    className="mt-4 h-2 overflow-hidden rounded-full bg-white/80"
                                    role="progressbar"
                                    aria-label="Passing rate"
                                    aria-valuemin={0}
                                    aria-valuemax={100}
                                    aria-valuenow={stats.passRate}
                                >
                                    <div className="h-full rounded-full bg-emerald-600 transition-all" style={{ width: `${stats.passRate}%` }} />
                                </div>
                            </div>
                            <div className="mt-5 grid grid-cols-3 gap-3">
                                <div className="rounded-lg border p-3">
                                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Check className="size-3.5 text-emerald-600" />
                                        Passed
                                    </p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.passed)}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <X className="size-3.5 text-rose-600" />
                                        Failed
                                    </p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.failed)}</p>
                                </div>
                                <div className="rounded-lg border p-3">
                                    <p className="text-xs text-muted-foreground">Pending</p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.pending)}</p>
                                </div>
                            </div>
                            <p className="mt-4 text-xs leading-relaxed text-muted-foreground">
                                Based on {numberFormatter.format(stats.passed + stats.failed)} completed results; pending grades are excluded.
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <Card id="recent-grades" className="overflow-hidden shadow-sm">
                    <CardHeader className="gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-lg">Recent grade records</CardTitle>
                            <CardDescription>The latest updates across your classes</CardDescription>
                        </div>
                        <div className="relative w-full sm:max-w-xs">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search student or subject"
                                className="pl-9"
                                aria-label="Search grade records"
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[760px] text-left text-sm">
                                <thead className="bg-muted/40 text-xs font-medium text-muted-foreground">
                                    <tr>
                                        <th scope="col" className="px-6 py-3.5">Student</th>
                                        <th scope="col" className="px-6 py-3.5">Subject</th>
                                        <th scope="col" className="px-6 py-3.5">Section</th>
                                        <th scope="col" className="px-6 py-3.5">Average</th>
                                        <th scope="col" className="px-6 py-3.5">Result</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {filteredGrades.map((grade) => (
                                        <tr key={grade.id} className="transition-colors hover:bg-muted/30">
                                            <td className="px-6 py-4">
                                                <p className="font-medium">{grade.student_name}</p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">LRN {grade.lrn}</p>
                                            </td>
                                            <td className="px-6 py-4">
                                                <p className="font-medium">{grade.subject_name}</p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">{grade.subject_code}</p>
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground">
                                                {grade.grade_level} · {grade.section_name}
                                            </td>
                                            <td className="px-6 py-4 font-semibold tabular-nums">{formatGrade(grade.average_grade)}</td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        grade.pass_fail_status === 'Passed'
                                                            ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100'
                                                            : grade.pass_fail_status === 'Failed'
                                                              ? 'bg-rose-100 text-rose-800 hover:bg-rose-100'
                                                              : 'bg-muted text-muted-foreground'
                                                    }
                                                >
                                                    {grade.pass_fail_status ?? 'Pending'}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredGrades.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center">
                                                <p className="text-sm font-medium">
                                                    {recentGrades.length === 0 ? 'No grade records yet' : 'No matching records'}
                                                </p>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {recentGrades.length === 0
                                                        ? 'Once grades are entered, the latest records will appear here.'
                                                        : 'Try a different student name, LRN, or subject.'}
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Separator />
                        <div className="flex flex-col gap-1 px-6 py-3.5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <span>
                                Showing {numberFormatter.format(filteredGrades.length)} of {numberFormatter.format(recentGrades.length)} recent records
                            </span>
                            <span className="inline-flex items-center gap-1">
                                Sorted by most recently updated <ArrowUpRight className="size-3.5" />
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
