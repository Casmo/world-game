import { Head } from '@inertiajs/react';
import { useState } from 'react';

type Tile = {
    h3_index: string;
    biome: string | null;
    terrain: string | null;
    base_resources: Record<string, unknown> | null;
    status: 'pending' | 'resolved';
    center: [number, number];
    team_id: number | null;
    is_owned: boolean;
    is_own_team: boolean;
};

function tileBorderClass(tile: Tile): string {
    if (tile.is_own_team) {
        return 'border-emerald-500';
    }
    if (tile.is_owned) {
        return 'border-rose-500';
    }
    return 'border-sidebar-border/70 hover:border-neutral-400 dark:border-sidebar-border';
}

type Props = {
    center: { h3_index: string; lat: number; lng: number };
    tiles: Tile[];
};

export default function WorldMap({ center, tiles }: Props) {
    const [selected, setSelected] = useState<Tile | null>(null);

    return (
        <>
            <Head title="World Map" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header>
                    <h1 className="text-xl font-semibold">World Map</h1>
                    <p className="text-sm text-neutral-500">
                        Centered on {center.lat.toFixed(4)}, {center.lng.toFixed(4)}
                    </p>
                </header>

                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                    {tiles.map((tile) => (
                        <button
                            key={tile.h3_index}
                            type="button"
                            onClick={() => setSelected(tile)}
                            className={`flex aspect-square flex-col items-center justify-center rounded-xl border-2 p-2 text-center transition ${tileBorderClass(tile)}`}
                            data-h3={tile.h3_index}
                            data-status={tile.status}
                            data-owned={tile.is_owned}
                        >
                            {tile.status === 'pending' ? (
                                <span className="animate-pulse text-xs text-neutral-400">
                                    Resolving…
                                </span>
                            ) : (
                                <span className="text-sm font-medium capitalize">
                                    {tile.biome ?? 'Unknown'}
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                {selected && (
                    <aside className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-semibold">Tile {selected.h3_index}</h2>
                        <dl className="mt-2 grid grid-cols-2 gap-1 text-sm">
                            <dt className="text-neutral-500">Biome</dt>
                            <dd className="capitalize">{selected.biome ?? '—'}</dd>
                            <dt className="text-neutral-500">Terrain</dt>
                            <dd className="capitalize">{selected.terrain ?? '—'}</dd>
                            <dt className="text-neutral-500">Status</dt>
                            <dd className="capitalize">{selected.status}</dd>
                        </dl>
                    </aside>
                )}
            </div>
        </>
    );
}
