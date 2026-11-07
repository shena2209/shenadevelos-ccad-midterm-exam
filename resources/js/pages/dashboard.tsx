import CreateRecordDialog from '@/components/dashboard/create-record-dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BookOpen, CheckCircle2, Download, GraduationCap, Layers3, Search, UsersRound, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

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

        if (!query) {
            return recentGrades;
        }

        return recentGrades.filter((grade) =>
            [grade.student_name, grade.lrn, grade.subject_name, grade.subject_code, grade.section_name].join(' ').toLowerCase().includes(query),
        );
    }, [recentGrades, search]);

    const cards = [
        { label: 'Total students', value: stats.students, icon: UsersRound, color: 'bg-sky-50 text-sky-700' },
        { label: 'Teachers', value: stats.teachers, icon: GraduationCap, color: 'bg-violet-50 text-violet-700' },
        { label: 'Subjects', value: stats.subjects, icon: BookOpen, color: 'bg-amber-50 text-amber-700' },
        { label: 'Sections', value: stats.sections, icon: Layers3, color: 'bg-emerald-50 text-emerald-700' },
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
            <main className="flex flex-1 flex-col gap-7 bg-slate-50/70 p-4 md:p-8">
                <section className="flex flex-col justify-between gap-5 md:flex-row md:items-end">
                    <div>
                        <p className="mb-2 text-sm font-medium text-emerald-700">School year overview</p>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Good to see you</h1>
                        <p className="mt-2 text-sm text-slate-600">Here’s a snapshot of your school’s academic records.</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 sm:flex">
                            <span className="size-2 rounded-full bg-emerald-500" />
                            Academic records
                        </div>
                        <CreateRecordDialog
                            options={options}
                            onCreated={(type) => setSuccessMessage(`${recordLabels[type]} created successfully.`)}
                        />
                        <button
                            type="button"
                            onClick={exportGrades}
                            disabled={filteredGrades.length === 0}
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Download className="size-4" />
                            Export records
                        </button>
                    </div>
                </section>

                {successMessage && (
                    <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {successMessage}
                    </p>
                )}

                <section aria-label="School statistics" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map(({ label, value, icon: Icon, color }) => (
                        <article key={label} className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/40">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm font-medium text-slate-600">{label}</p>
                                    <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{numberFormatter.format(value)}</p>
                                </div>
                                <span className={`flex size-11 items-center justify-center rounded-xl ${color}`}>
                                    <Icon className="size-5" />
                                </span>
                            </div>
                        </article>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,1fr)]">
                    <article id="performance" className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/40 md:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Subject performance</h2>
                                <p className="mt-1 text-sm text-slate-600">Average grade across recorded assessments</p>
                            </div>
                            <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Top 5 subjects</span>
                        </div>
                        {subjectPerformance.length > 0 ? (
                            <div className="mt-7 space-y-5">
                                {subjectPerformance.map((subject) => {
                                    const score = Number(subject.average_grade ?? 0);

                                    return (
                                        <div key={subject.code}>
                                            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                                                <div className="min-w-0">
                                                    <span className="font-medium text-slate-800">{subject.name}</span>
                                                    <span className="ml-2 text-xs font-medium text-slate-600">{subject.code}</span>
                                                </div>
                                                <span className="shrink-0 font-semibold text-slate-700 tabular-nums">
                                                    {formatGrade(subject.average_grade)}
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    className="h-full rounded-full bg-emerald-500 transition-all"
                                                    style={{ width: `${Math.min(100, Math.max(0, score))}%` }}
                                                />
                                            </div>
                                            <p className="mt-1.5 text-xs text-slate-600">
                                                {numberFormatter.format(subject.grade_count)}{' '}
                                                {subject.grade_count === 1 ? 'grade record' : 'grade records'}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="mt-7 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-5 py-9 text-center">
                                <BookOpen className="mx-auto size-6 text-slate-600" />
                                <p className="mt-3 text-sm font-medium text-slate-800">No subject grades yet</p>
                                <p className="mt-1 text-sm text-slate-600">Performance will appear here when grades are recorded.</p>
                            </div>
                        )}
                    </article>

                    <article className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/40 md:p-6">
                        <div>
                            <h2 className="text-base font-semibold text-slate-900">Grade summary</h2>
                            <p className="mt-1 text-sm text-slate-600">Overall results for recorded grades</p>
                        </div>
                        <div className="mt-6 rounded-xl bg-emerald-50/80 p-5">
                            <p className="text-sm font-medium text-emerald-800">Passing rate</p>
                            <div className="mt-2 flex items-end justify-between gap-3">
                                <p className="text-4xl font-semibold tracking-tight text-emerald-950">{stats.passRate}%</p>
                                <CheckCircle2 className="mb-1 size-6 text-emerald-600" />
                            </div>
                            <div className="mt-4 h-2 overflow-hidden rounded-full bg-white/80">
                                <div className="h-full rounded-full bg-emerald-500" style={{ width: `${stats.passRate}%` }} />
                            </div>
                        </div>
                        <div className="mt-4 grid grid-cols-3 gap-2">
                            <div className="rounded-xl border border-slate-100 p-4">
                                <div className="flex items-center gap-2 text-sm text-slate-700">
                                    <CheckCircle2 className="size-4 text-emerald-600" />
                                    Passed
                                </div>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{numberFormatter.format(stats.passed)}</p>
                            </div>
                            <div className="rounded-xl border border-slate-100 p-4">
                                <div className="flex items-center gap-2 text-sm text-slate-700">
                                    <XCircle className="size-4 text-rose-500" />
                                    Failed
                                </div>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{numberFormatter.format(stats.failed)}</p>
                            </div>
                            <div className="rounded-xl border border-slate-100 p-4">
                                <div className="text-sm font-medium text-slate-700">Pending</div>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{numberFormatter.format(stats.pending)}</p>
                            </div>
                        </div>
                        <p className="mt-4 text-xs leading-5 text-slate-600">
                            Passing rate uses {numberFormatter.format(stats.passed + stats.failed)} completed results; pending grades are excluded.
                        </p>
                    </article>
                </section>

                <section id="recent-grades" className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/40">
                    <div className="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 md:flex-row md:items-center md:justify-between md:px-6">
                        <div>
                            <h2 className="text-base font-semibold text-slate-900">Recent grade records</h2>
                            <p className="mt-1 text-sm text-slate-600">The latest updates across your classes</p>
                        </div>
                        <label className="relative block w-full md:max-w-xs">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-600" />
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search student or subject"
                                className="h-10 w-full rounded-xl border border-slate-300 bg-white pr-3 pl-9 text-sm text-slate-900 transition outline-none placeholder:text-slate-600 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                                aria-label="Search grade records"
                            />
                        </label>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="bg-slate-50/80 text-xs font-semibold tracking-wide text-slate-700 uppercase">
                                <tr>
                                    <th className="px-6 py-3.5">Student</th>
                                    <th className="px-6 py-3.5">Subject</th>
                                    <th className="px-6 py-3.5">Section</th>
                                    <th className="px-6 py-3.5">Average</th>
                                    <th className="px-6 py-3.5">Result</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredGrades.map((grade) => (
                                    <tr key={grade.id} className="transition hover:bg-slate-50/70">
                                        <td className="px-6 py-4">
                                            <p className="font-medium text-slate-800">{grade.student_name}</p>
                                            <p className="mt-0.5 text-xs text-slate-600">LRN {grade.lrn}</p>
                                        </td>
                                        <td className="px-6 py-4">
                                            <p className="font-medium text-slate-700">{grade.subject_name}</p>
                                            <p className="mt-0.5 text-xs text-slate-600">{grade.subject_code}</p>
                                        </td>
                                        <td className="px-6 py-4 text-slate-600">
                                            {grade.grade_level} · {grade.section_name}
                                        </td>
                                        <td className="px-6 py-4 font-semibold text-slate-800 tabular-nums">{formatGrade(grade.average_grade)}</td>
                                        <td className="px-6 py-4">
                                            {grade.pass_fail_status ? (
                                                <span
                                                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${
                                                        grade.pass_fail_status === 'Passed'
                                                            ? 'bg-emerald-50 text-emerald-800'
                                                            : 'bg-rose-50 text-rose-800'
                                                    }`}
                                                >
                                                    <span
                                                        className={`size-1.5 rounded-full ${grade.pass_fail_status === 'Passed' ? 'bg-emerald-500' : 'bg-rose-500'}`}
                                                    />
                                                    {grade.pass_fail_status}
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                    Pending
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {filteredGrades.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-12 text-center">
                                            <p className="text-sm font-medium text-slate-700">
                                                {recentGrades.length === 0 ? 'No grade records yet' : 'No matching records'}
                                            </p>
                                            <p className="mt-1 text-sm text-slate-600">
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
                    <div className="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-xs text-slate-600">
                        <span>
                            Showing {numberFormatter.format(filteredGrades.length)} of {numberFormatter.format(recentGrades.length)} recent records
                        </span>
                        <span>Sorted by most recently updated</span>
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
