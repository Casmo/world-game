import { Head } from '@inertiajs/react';

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
};

export default function City({ tile, grid, buildings }: Props) {
    const byPlot = new Map(buildings.map((b) => [`${b.plot_x},${b.plot_y}`, b]));
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

                <div
                    className="grid gap-1"
                    style={{ gridTemplateColumns: `repeat(${grid}, minmax(0, 1fr))` }}
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
