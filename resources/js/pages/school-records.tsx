import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';

interface SchoolRecord {
    id: number;
    name: string;
    detail: string;
}

interface SchoolRecordsProps {
    title: 'Students' | 'Teachers' | 'Subjects' | 'Sections';
    records: SchoolRecord[];
}

const singularLabels: Record<SchoolRecordsProps['title'], string> = {
    Students: 'student',
    Teachers: 'teacher',
    Subjects: 'subject',
    Sections: 'section',
};

export default function SchoolRecords({ title, records }: SchoolRecordsProps) {
    const [search, setSearch] = useState('');
    const [selectedRecord, setSelectedRecord] = useState<SchoolRecord | null>(null);
    const singularLabel = singularLabels[title];
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: `/school-records/${title.toLowerCase()}` },
    ];
    const filteredRecords = useMemo(() => {
        const query = search.trim().toLowerCase();

        return query ? records.filter((record) => `${record.name} ${record.detail}`.toLowerCase().includes(query)) : records;
    }, [records, search]);

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
                                    <button
                                        key={record.id}
                                        type="button"
                                        aria-label={`View ${singularLabel} record for ${record.name}`}
                                        onClick={() => setSelectedRecord(record)}
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
                            </div>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSelectedRecord(null)}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </main>
        </AppLayout>
    );
}
