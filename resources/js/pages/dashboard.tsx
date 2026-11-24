import CreateRecordDialog from '@/components/dashboard/create-record-dialog';
import GradeRecordActions from '@/components/dashboard/grade-record-actions';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ArrowUpRight, BookOpen, Check, CheckCircle2, GraduationCap, Layers3, Search, UsersRound, X, type LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

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
    student_id: number;
    subject_id: number;
    prelim_grade: number | string | null;
    midterm_grade: number | string | null;
    final_grade: number | string | null;
    student_name: string;
    lrn: string;
    subject_name: string;
    subject_code: string;
    grade_level: string;
    section_name: string;
    average_grade: number | string | null;
    pass_fail_status: 'Passed' | 'Failed' | null;
}

interface PendingGrade {
    id: number;
    student_name: string;
    lrn: string;
    grade_level: string;
    subject_name: string;
    subject_code: string;
    section_name: string;
    prelim_grade: number | string | null;
    midterm_grade: number | string | null;
    final_grade: number | string | null;
    average_grade?: number | string | null;
    pass_fail_status?: 'Passed' | 'Failed';
}

interface GradeOutcomeRecord extends PendingGrade {
    average_grade: number | string | null;
    pass_fail_status: 'Passed' | 'Failed';
}

interface SubjectPerformance {
    name: string;
    code: string;
    average_grade: number | string | null;
    grade_count: number;
    assessment_count: number;
}

interface DashboardOptions {
    students: { id: number; name: string; lrn: string; grade_level: string; section_id: number; section_name: string }[];
    teachers: { id: number; name: string }[];
    subjects: { id: number; name: string; code: string; teacher_id: number | null; teacher_name: string | null }[];
    sections: { id: number; name: string }[];
}

type SummaryType = 'students' | 'teachers' | 'subjects' | 'sections';
type GradeStatus = 'Passed' | 'Failed' | 'Pending';

interface DashboardProps {
    stats: DashboardStats;
    recentGrades: RecentGrade[];
    pendingGrades: PendingGrade[];
    gradeOutcomeRecords: GradeOutcomeRecord[];
    subjectPerformance: SubjectPerformance[];
    options: DashboardOptions;
}

const numberFormatter = new Intl.NumberFormat();
const summaryRecordLabels: Record<SummaryType, { title: string; noun: string }> = {
    students: { title: 'Enrolled students', noun: 'student' },
    teachers: { title: 'Teachers', noun: 'teacher' },
    subjects: { title: 'Subjects', noun: 'subject' },
    sections: { title: 'Sections', noun: 'section' },
};
const summaryHashTypes: Record<string, SummaryType> = {
    students: 'students',
    teachers: 'teachers',
    subjects: 'subjects',
    sections: 'sections',
};
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

export default function Dashboard({ stats, recentGrades, pendingGrades, gradeOutcomeRecords, subjectPerformance, options }: DashboardProps) {
    const page = usePage();
    const [search, setSearch] = useState('');
    const [successMessage, setSuccessMessage] = useState('');
    const [selectedSummary, setSelectedSummary] = useState<SummaryType | null>(null);
    const [selectedGradeStatus, setSelectedGradeStatus] = useState<GradeStatus | null>(null);
    const [selectedGrade, setSelectedGrade] = useState<RecentGrade | null>(null);
    useEffect(() => {
        function openSummaryFromHash() {
            const summary = summaryHashTypes[window.location.hash.slice(1)];
            if (summary) setSelectedSummary(summary);
        }

        openSummaryFromHash();
        window.addEventListener('hashchange', openSummaryFromHash);

        return () => window.removeEventListener('hashchange', openSummaryFromHash);
    }, []);
    useEffect(() => {
        const summary = summaryHashTypes[page.url.split('#')[1] ?? ''];
        if (summary) setSelectedSummary(summary);
    }, [page.url]);
    const filteredGrades = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) return recentGrades;

        return recentGrades.filter((grade) =>
            [grade.student_name, grade.lrn, grade.subject_name, grade.subject_code, grade.section_name].join(' ').toLowerCase().includes(query),
        );
    }, [recentGrades, search]);

    const summaryCards: { id: SummaryType; label: string; value: number; detail: string; icon: LucideIcon; accent: string }[] = [
        {
            id: 'students',
            label: 'Students enrolled',
            value: stats.students,
            detail: 'Learner records',
            icon: UsersRound,
            accent: 'bg-sky-50 text-sky-700',
        },
        {
            id: 'teachers',
            label: 'Teachers',
            value: stats.teachers,
            detail: 'Active teachers',
            icon: GraduationCap,
            accent: 'bg-violet-50 text-violet-700',
        },
        {
            id: 'subjects',
            label: 'Subjects',
            value: stats.subjects,
            detail: 'In the curriculum',
            icon: BookOpen,
            accent: 'bg-amber-50 text-amber-700',
        },
        { id: 'sections', label: 'Sections', value: stats.sections, detail: 'Class groups', icon: Layers3, accent: 'bg-emerald-50 text-emerald-700' },
    ];
    const selectedRecordLabel = selectedSummary ? summaryRecordLabels[selectedSummary] : null;
    const selectedGradeRecords =
        selectedGradeStatus === 'Pending'
            ? pendingGrades
            : selectedGradeStatus
              ? gradeOutcomeRecords.filter((grade) => grade.pass_fail_status === selectedGradeStatus)
              : [];
    const summaryRecords =
        selectedSummary === 'students'
            ? options.students.map((student) => ({
                  id: student.id,
                  name: student.name,
                  detail: `LRN ${student.lrn} · ${student.grade_level} · ${student.section_name}`,
              }))
            : selectedSummary === 'teachers'
              ? options.teachers.map((teacher) => ({ id: teacher.id, name: teacher.name, detail: 'Teacher' }))
              : selectedSummary === 'subjects'
                ? options.subjects.map((subject) => ({
                      id: subject.id,
                      name: subject.name,
                      detail: `${subject.code} · ${subject.teacher_name ?? 'No teacher assigned'}`,
                  }))
                : selectedSummary === 'sections'
                  ? options.sections.map((section) => {
                        const students = options.students.filter((student) => student.section_id === section.id);

                        return {
                            id: section.id,
                            name: section.name,
                            detail: students.length ? students.map((student) => student.name).join(', ') : 'No students assigned',
                        };
                    })
                  : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <main className="bg-muted/30 flex flex-1 flex-col gap-6 p-4 md:gap-8 md:p-8">
                <header className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                    <div className="space-y-2">
                        <Badge variant="secondary" className="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800 hover:bg-emerald-100">
                            <span className="mr-2 size-1.5 rounded-full bg-emerald-600" />
                            School year overview
                        </Badge>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">Academic dashboard</h1>
                        <p className="text-muted-foreground max-w-2xl text-sm md:text-base">
                            A clear view of enrollment, grade outcomes, and the latest classroom activity.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
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
                    {summaryCards.map(({ id, label, value, detail, icon: Icon, accent }) => (
                        <Card
                            key={label}
                            id={id}
                            role="button"
                            tabIndex={0}
                            aria-label={`${label}: ${numberFormatter.format(value)}. Click to view records.`}
                            aria-haspopup="dialog"
                            onClick={() => setSelectedSummary(id)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' || event.key === ' ') {
                                    event.preventDefault();
                                    setSelectedSummary(id);
                                }
                            }}
                            className="border-border/70 focus-visible:ring-ring cursor-pointer overflow-hidden shadow-sm transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:outline-hidden"
                        >
                            <CardContent className="flex items-start justify-between p-5">
                                <div className="space-y-3">
                                    <p className="text-muted-foreground text-sm font-medium">{label}</p>
                                    <div>
                                        <p className="text-3xl font-semibold tracking-tight tabular-nums">{numberFormatter.format(value)}</p>
                                        <p className="text-muted-foreground mt-1 text-xs">{detail} · Click to view</p>
                                    </div>
                                </div>
                                <span className={`flex size-11 items-center justify-center rounded-xl ${accent}`}>
                                    <Icon className="size-5" aria-hidden="true" />
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </section>
                <Dialog
                    open={selectedSummary !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setSelectedSummary(null);
                            if (window.location.hash) {
                                router.get('/dashboard', {}, { replace: true, preserveState: true, preserveScroll: true });
                            }
                        }
                    }}
                >
                    <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{selectedRecordLabel?.title ?? 'School records'}</DialogTitle>
                            <DialogDescription>
                                {selectedRecordLabel
                                    ? `${numberFormatter.format(summaryRecords.length)} ${selectedRecordLabel.noun} ${summaryRecords.length === 1 ? 'record' : 'records'} in your school data.`
                                    : 'Select a summary card to view its records.'}
                            </DialogDescription>
                        </DialogHeader>
                        {summaryRecords.length > 0 ? (
                            <div className="divide-y rounded-lg border">
                                {summaryRecords.map((record) => (
                                    <div key={record.id} className="px-4 py-3">
                                        <p className="font-medium">{record.name}</p>
                                        <p className="text-muted-foreground mt-1 text-sm">{record.detail}</p>
                                    </div>
                                ))}
                            </div>
                        ) : selectedRecordLabel ? (
                            <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                                No {selectedRecordLabel.noun} records yet.
                            </p>
                        ) : null}
                    </DialogContent>
                </Dialog>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(300px,1fr)]">
                    <Card id="performance" className="shadow-sm">
                        <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0">
                            <div className="space-y-1">
                                <CardTitle className="text-lg">Subject performance</CardTitle>
                                <CardDescription>Average of the assessment grades entered for each subject</CardDescription>
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
                                                        <p className="text-muted-foreground text-xs">{subject.code}</p>
                                                    </div>
                                                    <p className="shrink-0 text-sm font-semibold tabular-nums">
                                                        {formatGrade(subject.average_grade)}
                                                    </p>
                                                </div>
                                                {subject.assessment_count > 0 ? (
                                                    <div
                                                        className="bg-muted h-2 overflow-hidden rounded-full"
                                                        role="progressbar"
                                                        aria-label={`${subject.name} average`}
                                                        aria-valuemin={0}
                                                        aria-valuemax={100}
                                                        aria-valuenow={width}
                                                    >
                                                        <div
                                                            className="h-full rounded-full bg-emerald-600 transition-all"
                                                            style={{ width: `${width}%` }}
                                                        />
                                                    </div>
                                                ) : (
                                                    <p className="text-muted-foreground text-xs">No assessment scores entered yet</p>
                                                )}
                                                <p className="text-muted-foreground text-xs">
                                                    {numberFormatter.format(subject.grade_count)}{' '}
                                                    {subject.grade_count === 1 ? 'grade record' : 'grade records'} ·{' '}
                                                    {numberFormatter.format(subject.assessment_count)}{' '}
                                                    {subject.assessment_count === 1 ? 'assessment score' : 'assessment scores'}
                                                </p>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed p-8 text-center">
                                    <BookOpen className="text-muted-foreground/60 mx-auto size-8" />
                                    <p className="mt-3 text-sm font-medium">No subject data yet</p>
                                    <p className="text-muted-foreground mt-1 text-sm">Subject averages will appear here after grades are recorded.</p>
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
                                <button
                                    type="button"
                                    aria-label={`View ${numberFormatter.format(stats.passed)} passed grade records`}
                                    onClick={() => setSelectedGradeStatus('Passed')}
                                    className="hover:bg-muted/40 focus-visible:ring-ring rounded-lg border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                        <Check className="size-3.5 text-emerald-600" />
                                        Passed
                                    </p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.passed)}</p>
                                </button>
                                <button
                                    type="button"
                                    aria-label={`View ${numberFormatter.format(stats.failed)} failed grade records`}
                                    onClick={() => setSelectedGradeStatus('Failed')}
                                    className="hover:bg-muted/40 focus-visible:ring-ring rounded-lg border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                        <X className="size-3.5 text-rose-600" />
                                        Failed
                                    </p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.failed)}</p>
                                </button>
                                <button
                                    type="button"
                                    aria-label={`View ${numberFormatter.format(stats.pending)} pending grade records`}
                                    onClick={() => setSelectedGradeStatus('Pending')}
                                    className="hover:bg-muted/40 focus-visible:ring-ring rounded-lg border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    <p className="text-muted-foreground text-xs">Pending</p>
                                    <p className="mt-2 text-xl font-semibold tabular-nums">{numberFormatter.format(stats.pending)}</p>
                                </button>
                            </div>
                            <p className="text-muted-foreground mt-4 text-xs leading-relaxed">
                                Pending records are missing at least one assessment grade.
                            </p>
                        </CardContent>
                    </Card>
                </section>
                <Dialog open={selectedGradeStatus !== null} onOpenChange={(open) => !open && setSelectedGradeStatus(null)}>
                    <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{selectedGradeStatus ?? 'Grade'} grade records</DialogTitle>
                            <DialogDescription>
                                {selectedGradeStatus === 'Pending'
                                    ? 'A record remains pending until Prelim, Midterm, and Final grades are all entered.'
                                    : selectedGradeStatus === 'Passed'
                                      ? 'Completed grade records with an average of 75 or higher.'
                                      : selectedGradeStatus === 'Failed'
                                        ? 'Completed grade records with an average below 75.'
                                        : 'View grade records by result.'}
                            </DialogDescription>
                        </DialogHeader>
                        {selectedGradeRecords.length > 0 ? (
                            <div className="divide-y rounded-lg border">
                                {selectedGradeRecords.map((grade) => {
                                    const missing = [
                                        grade.prelim_grade === null ? 'Prelim' : null,
                                        grade.midterm_grade === null ? 'Midterm' : null,
                                        grade.final_grade === null ? 'Final' : null,
                                    ].filter((assessment): assessment is string => assessment !== null);
                                    const resultDetail =
                                        grade.average_grade !== undefined
                                            ? `Average ${formatGrade(grade.average_grade)}`
                                            : missing.length > 0
                                              ? `Missing ${missing.join(', ')}`
                                              : 'All assessments entered; result pending';

                                    return (
                                        <div key={grade.id} className="flex items-start justify-between gap-4 px-4 py-3">
                                            <div>
                                                <p className="font-medium">{grade.student_name}</p>
                                                <p className="text-muted-foreground mt-1 text-xs">LRN {grade.lrn}</p>
                                                <p className="text-muted-foreground mt-1 text-sm">
                                                    {grade.subject_name} ({grade.subject_code}) · {grade.grade_level} · {grade.section_name}
                                                </p>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    Prelim {formatGrade(grade.prelim_grade)} · Midterm {formatGrade(grade.midterm_grade)} · Final{' '}
                                                    {formatGrade(grade.final_grade)}
                                                </p>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className={
                                                    selectedGradeStatus === 'Passed'
                                                        ? 'shrink-0 bg-emerald-100 text-emerald-900'
                                                        : selectedGradeStatus === 'Failed'
                                                          ? 'shrink-0 bg-rose-100 text-rose-900'
                                                          : 'shrink-0 bg-amber-100 text-amber-900'
                                                }
                                            >
                                                {resultDetail}
                                            </Badge>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                                No {selectedGradeStatus?.toLowerCase() ?? 'grade'} grade records.
                            </p>
                        )}
                    </DialogContent>
                </Dialog>

                <Card id="recent-grades" className="overflow-hidden shadow-sm">
                    <CardHeader className="gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-lg">Recent grade records</CardTitle>
                            <CardDescription>Click a student&apos;s name or the eye icon to view full grade details.</CardDescription>
                        </div>
                        <div className="relative w-full sm:max-w-xs">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
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
                                <thead className="bg-muted/40 text-muted-foreground text-xs font-medium">
                                    <tr>
                                        <th scope="col" className="px-6 py-3.5">
                                            Student
                                        </th>
                                        <th scope="col" className="px-6 py-3.5">
                                            Subject
                                        </th>
                                        <th scope="col" className="px-6 py-3.5">
                                            Section
                                        </th>
                                        <th scope="col" className="px-6 py-3.5">
                                            Average
                                        </th>
                                        <th scope="col" className="px-6 py-3.5">
                                            Result
                                        </th>
                                        <th scope="col" className="px-6 py-3.5 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {filteredGrades.map((grade) => (
                                        <tr key={grade.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-6 py-4">
                                                <button
                                                    type="button"
                                                    className="focus-visible:ring-ring rounded-sm text-left font-medium hover:underline focus-visible:ring-2 focus-visible:outline-hidden"
                                                    aria-label={`View grade details for ${grade.student_name}`}
                                                    onClick={() => setSelectedGrade(grade)}
                                                >
                                                    {grade.student_name}
                                                </button>
                                                <p className="text-muted-foreground mt-0.5 text-xs">LRN {grade.lrn}</p>
                                            </td>
                                            <td className="px-6 py-4">
                                                <p className="font-medium">{grade.subject_name}</p>
                                                <p className="text-muted-foreground mt-0.5 text-xs">{grade.subject_code}</p>
                                            </td>
                                            <td className="text-muted-foreground px-6 py-4">
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
                                            <td className="px-6 py-4 text-right">
                                                <GradeRecordActions
                                                    grade={grade}
                                                    options={options}
                                                    onView={() => setSelectedGrade(grade)}
                                                    onChanged={(action) =>
                                                        setSuccessMessage(
                                                            action === 'updated'
                                                                ? 'Grade record updated successfully.'
                                                                : 'Grade record deleted successfully.',
                                                        )
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredGrades.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-6 py-12 text-center">
                                                <p className="text-sm font-medium">
                                                    {recentGrades.length === 0 ? 'No grade records yet' : 'No matching records'}
                                                </p>
                                                <p className="text-muted-foreground mt-1 text-sm">
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
                        <div className="text-muted-foreground flex flex-col gap-1 px-6 py-3.5 text-xs sm:flex-row sm:items-center sm:justify-between">
                            <span>
                                Showing {numberFormatter.format(filteredGrades.length)} of {numberFormatter.format(recentGrades.length)} recent
                                records
                            </span>
                            <span className="inline-flex items-center gap-1">
                                Sorted by most recently updated <ArrowUpRight className="size-3.5" />
                            </span>
                        </div>
                    </CardContent>
                </Card>
                <Dialog open={selectedGrade !== null} onOpenChange={(open) => !open && setSelectedGrade(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Grade record details</DialogTitle>
                            <DialogDescription>Assessment grades and student information for this record.</DialogDescription>
                        </DialogHeader>
                        {selectedGrade && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-muted-foreground text-xs">Student</p>
                                    <p className="font-medium">{selectedGrade.student_name}</p>
                                    <p className="text-muted-foreground text-sm">LRN {selectedGrade.lrn}</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">Class</p>
                                    <p className="font-medium">
                                        {selectedGrade.grade_level} · {selectedGrade.section_name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">Subject</p>
                                    <p className="font-medium">
                                        {selectedGrade.subject_name} ({selectedGrade.subject_code})
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground text-xs">Result</p>
                                    <p className="font-medium">{selectedGrade.pass_fail_status ?? 'Pending'}</p>
                                </div>
                                {[
                                    ['Prelim', selectedGrade.prelim_grade],
                                    ['Midterm', selectedGrade.midterm_grade],
                                    ['Final', selectedGrade.final_grade],
                                    ['Average', selectedGrade.average_grade],
                                ].map(([label, value]) => (
                                    <div key={label}>
                                        <p className="text-muted-foreground text-xs">{label}</p>
                                        <p className="font-medium tabular-nums">{formatGrade(value)}</p>
                                    </div>
                                ))}
                            </div>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSelectedGrade(null)}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </main>
        </AppLayout>
    );
}
