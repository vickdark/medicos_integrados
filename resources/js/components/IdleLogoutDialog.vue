<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useIdleLogout } from '@/composables/useIdleLogout';

const { warning, secondsLeft, stayConnected, logoutNow } = useIdleLogout();
</script>

<template>
    <Dialog :open="warning">
        <DialogContent
            class="sm:max-w-md"
            :show-close-button="false"
            @interact-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader>
                <DialogTitle>¿Sigues ahí?</DialogTitle>
                <DialogDescription>
                    Por seguridad de la información clínica, tu sesión se
                    cerrará en {{ secondsLeft }}
                    {{ secondsLeft === 1 ? 'segundo' : 'segundos' }} por
                    inactividad.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="logoutNow">
                    Cerrar sesión
                </Button>
                <Button @click="stayConnected">Seguir conectado</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
