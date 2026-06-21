import { Head } from '@inertiajs/react';

type Props = {
    balance: number;
    treasury: number;
    team: { name: string };
};

export default function Wallet({ balance, treasury, team }: Props) {
    return (
        <>
            <Head title="Wallet" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Wallet</h1>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-neutral-500">Your balance</p>
                        <p className="text-2xl font-semibold tabular-nums">{balance}</p>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-neutral-500">{team.name} treasury</p>
                        <p className="text-2xl font-semibold tabular-nums">{treasury}</p>
                    </div>
                </div>
            </div>
        </>
    );
}
