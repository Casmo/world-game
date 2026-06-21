import { Head, router } from '@inertiajs/react';

type Unit = {
    id: number;
    type: string;
    status: string;
    tile_id: string | null;
};

type Props = {
    units: Unit[];
};

export default function Units({ units }: Props) {
    const disband = (id: number) =>
        router.delete('/units', { data: { units: [id] } });

    return (
        <>
            <Head title="Forces" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header>
                    <h1 className="text-xl font-semibold">Forces</h1>
                    <p className="text-sm text-neutral-500">
                        Your Team&apos;s standing Units.
                    </p>
                </header>

                {units.length === 0 ? (
                    <p className="text-sm text-neutral-400">
                        No Units yet — train some at a Barracks.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="text-neutral-500">
                                <tr>
                                    <th className="py-2">Type</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2">Tile</th>
                                    <th className="py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {units.map((unit) => (
                                    <tr
                                        key={unit.id}
                                        className="border-t border-sidebar-border/70 dark:border-sidebar-border"
                                        data-unit={unit.id}
                                    >
                                        <td className="py-2 font-medium capitalize">
                                            {unit.type}
                                        </td>
                                        <td className="py-2 capitalize">
                                            {unit.status.replace('_', ' ')}
                                        </td>
                                        <td className="py-2 text-neutral-500">
                                            {unit.tile_id ?? '—'}
                                        </td>
                                        <td className="py-2 text-right">
                                            <button
                                                type="button"
                                                className="rounded bg-neutral-200 px-2 py-0.5 text-xs dark:bg-neutral-700"
                                                onClick={() => disband(unit.id)}
                                            >
                                                Disband
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
