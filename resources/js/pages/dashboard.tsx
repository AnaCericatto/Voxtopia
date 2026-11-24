import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex max-w-3xl flex-col items-center gap-2 p-10 text-center">
                <h1 className="text-2xl font-semibold">No builds yet</h1>
                <p className="text-muted-foreground">
                    Your builds will appear here once the editor is ready.
                </p>
            </div>
        </>
    );
}
