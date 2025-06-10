export interface Note {
    id: number;
    title: string;
    content: string;
    tags: string[] | null;
    created_at: string;
    updated_at: string;
}

export interface NoteFormData {
    title: string;
    content: string;
    tags: string[];
}

export interface AIEnhancementResponse {
    result: string;
    action: string;
} 