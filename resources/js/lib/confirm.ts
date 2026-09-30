import Swal from 'sweetalert2';
import type { SweetAlertIcon } from 'sweetalert2';

export type ConfirmTone = 'danger' | 'success' | 'primary' | 'warning';

type ConfirmOptions = {
    title: string;
    text?: string;
    confirmText?: string;
    cancelText?: string;
    tone?: ConfirmTone;
};

const buttonBase =
    'inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';

const confirmTones: Record<ConfirmTone, string> = {
    danger: 'bg-red-600 text-white hover:bg-red-700',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700',
    warning: 'bg-amber-500 text-white hover:bg-amber-600',
    primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
};

const icons: Record<ConfirmTone, SweetAlertIcon> = {
    danger: 'warning',
    success: 'question',
    warning: 'warning',
    primary: 'question',
};

/**
 * Themed confirmation dialog (SweetAlert2). Resolves to true only when the
 * user accepts; closing it or pressing cancel resolves to false.
 */
export async function confirmAction({
    title,
    text,
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    tone = 'primary',
}: ConfirmOptions): Promise<boolean> {
    const result = await Swal.fire({
        title,
        text,
        icon: icons[tone],
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
        focusCancel: tone === 'danger',
        buttonsStyling: false,
        background: 'var(--background)',
        color: 'var(--foreground)',
        customClass: {
            popup: 'rounded-xl border !shadow-lg',
            title: '!text-lg !font-semibold',
            htmlContainer: '!text-sm !text-muted-foreground',
            actions: 'gap-2',
            confirmButton: `${buttonBase} ${confirmTones[tone]}`,
            cancelButton: `${buttonBase} border bg-background hover:bg-accent`,
        },
    });

    return result.isConfirmed;
}
