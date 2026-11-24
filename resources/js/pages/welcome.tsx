import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <section className="mx-auto flex max-w-3xl flex-col items-center gap-6 px-4 py-24 text-center">
                <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
                    Plan your voxel builds, then show others how to build them
                </h1>

                <p className="text-lg text-muted-foreground">
                    {name} lets you design in 2D and 3D, share your creation,
                    and walk people through it step by step.
                </p>

                <Button asChild size="lg">
                    {auth.user ? (
                        <Link href={dashboard()}>Go to your dashboard</Link>
                    ) : (
                        <Link href={register()}>Create a free account</Link>
                    )}
                </Button>
            </section>
        </>
    );
}
