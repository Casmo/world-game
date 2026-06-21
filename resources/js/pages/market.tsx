import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

type PriceRow = {
    type: string;
    label: string;
    floor: number;
    ceiling: number;
};

type Props = {
    prices: PriceRow[];
    treasury: number;
    stockpile: Record<string, number>;
};

export default function Market({ prices, treasury, stockpile }: Props) {
    const [quantities, setQuantities] = useState<Record<string, number>>({});

    const qty = (type: string) => Math.max(1, quantities[type] ?? 1);
    const setQty = (type: string, value: number) =>
        setQuantities((q) => ({ ...q, [type]: value }));

    const trade = (action: 'sell' | 'buy', type: string) =>
        router.post(`/market/${action}`, {
            resource: type,
            quantity: qty(type),
        });

    return (
        <>
            <Head title="World Market" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <header className="flex items-baseline justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">World Market</h1>
                        <p className="text-sm text-neutral-500">
                            The NPC buys at the floor price and sells at the
                            ceiling price.
                        </p>
                    </div>
                    <p className="text-sm text-neutral-500">
                        Treasury:{' '}
                        <span className="font-semibold tabular-nums">
                            {treasury}
                        </span>
                    </p>
                </header>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="text-neutral-500">
                            <tr>
                                <th className="py-2">Resource</th>
                                <th className="py-2 text-right">In stock</th>
                                <th className="py-2 text-right">
                                    Sell @ floor
                                </th>
                                <th className="py-2 text-right">
                                    Buy @ ceiling
                                </th>
                                <th className="py-2 text-right">Quantity</th>
                                <th className="py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {prices.map((row) => (
                                <tr
                                    key={row.type}
                                    className="border-t border-sidebar-border/70 dark:border-sidebar-border"
                                    data-resource={row.type}
                                >
                                    <td className="py-2 font-medium">
                                        {row.label}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {stockpile[row.type] ?? 0}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {row.floor}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {row.ceiling}
                                    </td>
                                    <td className="py-2 text-right">
                                        <input
                                            type="number"
                                            min={1}
                                            value={qty(row.type)}
                                            onChange={(e) =>
                                                setQty(
                                                    row.type,
                                                    Number(e.target.value),
                                                )
                                            }
                                            className="w-16 rounded border border-sidebar-border/70 px-1 py-0.5 text-right dark:border-sidebar-border"
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        <button
                                            type="button"
                                            className="mr-1 rounded bg-neutral-200 px-2 py-0.5 text-xs dark:bg-neutral-700"
                                            onClick={() =>
                                                trade('sell', row.type)
                                            }
                                        >
                                            Sell
                                        </button>
                                        <button
                                            type="button"
                                            className="rounded bg-neutral-900 px-2 py-0.5 text-xs text-white dark:bg-white dark:text-neutral-900"
                                            onClick={() =>
                                                trade('buy', row.type)
                                            }
                                        >
                                            Buy
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
