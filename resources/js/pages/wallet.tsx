import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

type Props = {
    balance: number;
    treasury: number;
    team: { name: string };
    wageShare: number;
    canSetWageShare: boolean;
};

export default function Wallet({
    balance,
    treasury,
    team,
    wageShare,
    canSetWageShare,
}: Props) {
    const [share, setShare] = useState(wageShare);

    return (
        <>
            <Head title="Wallet" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Wallet</h1>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-neutral-500">Your balance</p>
                        <p className="text-2xl font-semibold tabular-nums">
                            {balance}
                        </p>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-neutral-500">
                            {team.name} treasury
                        </p>
                        <p className="text-2xl font-semibold tabular-nums">
                            {treasury}
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <p className="text-sm text-neutral-500">
                        Wage share — the fraction of a production shift's value
                        paid to workers.
                    </p>
                    {canSetWageShare ? (
                        <form
                            className="mt-2 flex items-center gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                router.post('/team/wage-share', {
                                    wage_share: share,
                                });
                            }}
                        >
                            <input
                                type="number"
                                step="0.01"
                                min={0}
                                max={1}
                                value={share}
                                onChange={(e) =>
                                    setShare(Number(e.target.value))
                                }
                                className="w-24 rounded border border-sidebar-border/70 px-2 py-1 tabular-nums dark:border-sidebar-border"
                            />
                            <button
                                type="submit"
                                className="rounded bg-neutral-900 px-3 py-1 text-sm text-white dark:bg-white dark:text-neutral-900"
                            >
                                Save
                            </button>
                        </form>
                    ) : (
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {wageShare}
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
