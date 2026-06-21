import { Head, router } from '@inertiajs/react';

type CityBuilding = {
    id: number;
    type: string;
    plot_x: number;
    plot_y: number;
    state: 'under_construction' | 'built';
    work_done: number;
    work_required: number;
    work_slots: number;
    active_builders: number;
};

type Props = {
    tile: { h3_index: string; biome: string | null };
    grid: number;
    buildings: CityBuilding[];
    resources: Record<string, number>;
    experience: Record<string, number>;
};

export default function City({
    tile,
    grid,
    buildings,
    resources,
    experience,
}: Props) {
    const byPlot = new Map(
        buildings.map((b) => [`${b.plot_x},${b.plot_y}`, b]),
    );
    const cells = Array.from({ length: grid * grid }, (_, i) => ({
        x: i % grid,
        y: Math.floor(i / grid),
    }));

    return (
        <>
            <Head title="City" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header>
                    <h1 className="text-xl font-semibold">City</h1>
                    <p className="text-sm text-neutral-500">
                        Tile {tile.h3_index}
                        {tile.biome ? ` · ${tile.biome}` : ''}
                    </p>
                </header>

                <section className="flex flex-wrap gap-6 text-sm">
                    <div>
                        <h2 className="font-medium text-neutral-500">
                            Team Resources
                        </h2>
                        {Object.keys(resources).length === 0 ? (
                            <p className="text-neutral-400">None yet</p>
                        ) : (
                            <ul className="flex gap-4">
                                {Object.entries(resources).map(
                                    ([type, amount]) => (
                                        <li key={type} className="capitalize">
                                            {type}:{' '}
                                            <span className="font-semibold">
                                                {amount}
                                            </span>
                                        </li>
                                    ),
                                )}
                            </ul>
                        )}
                    </div>
                    <div>
                        <h2 className="font-medium text-neutral-500">
                            Your Experience
                        </h2>
                        {Object.keys(experience).length === 0 ? (
                            <p className="text-neutral-400">None yet</p>
                        ) : (
                            <ul className="flex gap-4">
                                {Object.entries(experience).map(
                                    ([type, points]) => (
                                        <li key={type} className="capitalize">
                                            {type.replace('_', ' ')}:{' '}
                                            <span className="font-semibold">
                                                {points}
                                            </span>
                                        </li>
                                    ),
                                )}
                            </ul>
                        )}
                    </div>
                </section>

                <div
                    className="grid gap-1"
                    style={{
                        gridTemplateColumns: `repeat(${grid}, minmax(0, 1fr))`,
                    }}
                >
                    {cells.map(({ x, y }) => {
                        const building = byPlot.get(`${x},${y}`);

                        return (
                            <div
                                key={`${x},${y}`}
                                className="flex aspect-square flex-col items-center justify-center rounded border border-sidebar-border/70 p-1 text-center text-[10px] dark:border-sidebar-border"
                                data-plot={`${x},${y}`}
                            >
                                {building && (
                                    <>
                                        <span className="font-medium capitalize">
                                            {building.type.replace('_', ' ')}
                                        </span>
                                        <span className="text-neutral-500">
                                            {building.state === 'built'
                                                ? 'built'
                                                : `${building.work_done}/${building.work_required}`}
                                        </span>
                                        {building.state === 'built' ? (
                                            <button
                                                type="button"
                                                className="mt-0.5 rounded bg-neutral-900 px-1 py-0.5 text-[9px] text-white dark:bg-white dark:text-neutral-900"
                                                onClick={() =>
                                                    router.post(
                                                        `/buildings/${building.id}/work`,
                                                    )
                                                }
                                            >
                                                Work
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                className="mt-0.5 rounded bg-neutral-200 px-1 py-0.5 text-[9px] dark:bg-neutral-700"
                                                onClick={() =>
                                                    router.post(
                                                        `/buildings/${building.id}/construct`,
                                                    )
                                                }
                                            >
                                                Help build
                                            </button>
                                        )}
                                    </>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </>
    );
}
