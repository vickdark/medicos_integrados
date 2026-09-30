export type Option = {
    value: string;
    label: string;
};

export type RoleValue = 'admin' | 'doctor' | 'receptionist' | 'patient';

export type AppointmentStatusValue =
    'requested' | 'confirmed' | 'completed' | 'cancelled';

export type TableFilters = {
    search: string;
    status: string;
    action: string;
    role: string;
    patient_id: number | null;
    from: string;
    to: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PersonReference = {
    id: number;
    full_name: string;
};

export type DoctorReference = {
    id: number;
    name: string;
    specialty: string;
};

export type Patient = {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    document_type: Option | null;
    document_number: string | null;
    email: string | null;
    phone: string | null;
    birth_date: string | null;
    age: number | null;
    gender: Option | null;
    address: string | null;
    blood_type: string | null;
    insurer: { id: number; name: string } | null;
    affiliation_type: Option | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    has_account: boolean;
    can?: { update: boolean; create_consultation: boolean };
    allergies?: string | null;
    chronic_conditions?: string | null;
    medical_background?: string | null;
    created_at: string;
};

export type Appointment = {
    id: number;
    scheduled_at: string;
    status: Option & { value: AppointmentStatusValue };
    payment_status: Option | null;
    pending_payment_id: number | null;
    reason: string;
    notes: string | null;
    patient?: PersonReference;
    doctor?: DoctorReference;
    consultation_id?: number | null;
    can: {
        view_consultation: boolean;
        reschedule: boolean;
        update_status: boolean;
        register_payment: boolean;
        settle_payment: boolean;
    };
};

export type Prescription = {
    id: number;
    medication: string;
    dosage: string;
    frequency: string;
    duration: string | null;
    instructions: string | null;
};

export type CodedDiagnosis = {
    id: number;
    code: string;
    description: string;
};

export type ConsultationAddendum = {
    id: number;
    section: Option;
    reason: string;
    content: string;
    author: string;
    created_at: string;
};

export type Consultation = {
    id: number;
    consulted_at: string;
    reason: string;
    symptoms: string | null;
    diagnosis: string;
    primary_diagnosis?: CodedDiagnosis | null;
    diagnosis_type?: Option | null;
    related_diagnoses?: CodedDiagnosis[];
    addenda?: ConsultationAddendum[];
    addenda_count?: number;
    treatment: string | null;
    notes?: string | null;
    weight_kg: string | null;
    height_cm: string | null;
    blood_pressure: string | null;
    temperature_c: string | null;
    heart_rate: number | null;
    patient?: PersonReference;
    doctor?: DoctorReference;
    prescriptions?: Prescription[];
    attachments?: Attachment[];
};

export type Attachment = {
    id: number;
    original_name: string;
    mime_type: string;
    size: number;
    description: string | null;
    created_at: string;
};

export type Payment = {
    id: number;
    amount: string;
    method: Option;
    status: Option;
    concept: string;
    reference: string | null;
    paid_at: string | null;
    notes: string | null;
    patient?: PersonReference;
    appointment?: {
        id: number;
        scheduled_at: string;
        reason: string;
        status: Option;
        doctor: string;
    } | null;
    recorded_by?: string | null;
    can: { mark_paid: boolean; void: boolean; download_invoice: boolean };
    created_at: string;
};

export type Doctor = {
    id: number;
    name: string;
    email: string;
    specialty: string;
    license_number: string;
    phone: string | null;
    consultation_fee: string;
    bio: string | null;
    user_id: number;
    is_active: boolean;
    photo_url: string | null;
    slot_minutes: number;
};

export type ChartBar = {
    label: string;
    value: number;
    current?: boolean;
    today?: boolean;
};

export type StaffInsights = {
    kpis: {
        patients: number;
        patients_new_month: number;
        appointments_today: number;
        appointments_today_open: number;
        requests: number;
        income_month: number;
        income_delta: number | null;
        pending_amount: number;
        pending_count: number;
    };
    income_by_month: ChartBar[];
    appointments_by_day: ChartBar[];
    today_agenda: Appointment[];
    pending_requests: Appointment[];
    pending_payments: {
        id: number;
        patient: string;
        concept: string;
        amount: string;
        appointment_at: string | null;
    }[];
    top_doctors: {
        id: number;
        name: string;
        specialty: string;
        appointments: number;
    }[];
};

export type DoctorInsights = {
    kpis: {
        appointments_today: number;
        appointments_today_open: number;
        requests: number;
        patients: number;
        consultations_month: number;
        consultations_delta: number | null;
    };
    next_appointment: Appointment | null;
    today_agenda: Appointment[];
    pending_requests: Appointment[];
    week_load: ChartBar[];
    consultations_by_month: ChartBar[];
    recent_consultations: {
        id: number;
        patient: string;
        patient_id: number;
        reason: string;
        consulted_at: string;
    }[];
};
