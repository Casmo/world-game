import { Head } from '@inertiajs/react';

type TechStatus = 'unlocked' | 'available' | 'locked';

type TechNode = {
    type: string;
    label: string;
    prerequisites: string[];
    cost: number;
    status: TechStatus;
};

type Props = {
    buildings: TechNode[];
};

const statusStyles: Record<TechStatus, string> = {
    unlocked: 'border-green-500/60 bg-green-50 dark:bg-green-950/30',
    available: 'border-amber-500/60 bg-amber-50 dark:bg-amber-950/30',
    locked: 'border-sidebar-border/70 opacity-60 dark:border-sidebar-border',
};

export default function TechTree({ buildings }: Props) {
    return (
        <>
            <Head title="Tech Tree" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header>
                    <h1 className="text-xl font-semibold">Tech Tree</h1>
                    <p className="text-sm text-neutral-500">
                        Research unlocks one Building at a time.
                    </p>
                </header>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {buildings.map((node) => (
                        <div
                            key={node.type}
                            className={`rounded-xl border p-4 ${statusStyles[node.status]}`}
                            data-building={node.type}
                            data-status={node.status}
                        >
                            <div className="flex items-center justify-between">
                                <h2 className="font-medium">{node.label}</h2>
                                <span className="text-xs tracking-wide text-neutral-500 uppercase">
                                    {node.status}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-neutral-500">
                                {node.status === 'unlocked'
                                    ? 'Unlocked'
                                    : `Research cost: ${node.cost}`}
                            </p>
                            {node.prerequisites.length > 0 && (
                                <p className="mt-1 text-xs text-neutral-500">
                                    Requires:{' '}
                                    {node.prerequisites
                                        .map((p) => p.replace('_', ' '))
                                        .join(', ')}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
