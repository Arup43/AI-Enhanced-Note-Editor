import { Head, Link, useForm } from '@inertiajs/react';
import { FileText, Plus, Search, Tag, Trash2, Edit3 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Note } from '@/types/notes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    notes: Note[];
}

export default function Dashboard({ notes }: DashboardProps) {
    const [searchTerm, setSearchTerm] = useState('');
    const { delete: deleteNote } = useForm();
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [noteToDelete, setNoteToDelete] = useState<number | null>(null);

    const filteredNotes = notes.filter(
        (note) =>
            note.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            note.content.toLowerCase().includes(searchTerm.toLowerCase()) ||
            note.tags?.some((tag) => tag.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const openDeleteDialog = (noteId: number) => {
        setNoteToDelete(noteId);
        setIsDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (noteToDelete) {
            deleteNote(route('notes.destroy', noteToDelete));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Notes" />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">My Notes</h1>
                        <p className="text-muted-foreground">
                            Manage your notes with AI-powered enhancements
                        </p>
                    </div>
                    <Link href={route('notes.create')}>
                        <Button className="gap-2">
                            <Plus className="h-4 w-4" />
                            New Note
                        </Button>
                    </Link>
                </div>

                {/* Search */}
                <div className="relative max-w-md">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search notes..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="pl-10"
                    />
                </div>

                {/* Notes Grid */}
                {filteredNotes.length === 0 ? (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 className="mt-4 text-lg font-semibold">No notes found</h3>
                            <p className="mt-2 text-muted-foreground">
                                {searchTerm
                                    ? 'Try adjusting your search terms'
                                    : 'Get started by creating your first note'}
                            </p>
                            {!searchTerm && (
                                <Link href={route('notes.create')} className="mt-4 inline-block">
                                    <Button>Create your first note</Button>
                                </Link>
                            )}
                        </div>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filteredNotes.map((note) => (
                            <Card key={note.id} className="group hover:shadow-md transition-shadow">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <CardTitle className="line-clamp-2 text-lg">
                                            {note.title}
                                        </CardTitle>
                                        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Link href={route('notes.edit', note.id)}>
                                                <Button variant="ghost" size="sm">
                                                    <Edit3 className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(note.id)}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    <CardDescription className="text-xs text-muted-foreground">
                                        {note.updated_at}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground line-clamp-3 mb-4">
                                        {note.content}
                                    </p>
                                    {note.tags && note.tags.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {note.tags.slice(0, 3).map((tag, index) => (
                                                <Badge key={index} variant="secondary" className="text-xs">
                                                    <Tag className="h-3 w-3 mr-1" />
                                                    {tag}
                                                </Badge>
                                            ))}
                                            {note.tags.length > 3 && (
                                                <Badge variant="outline" className="text-xs">
                                                    +{note.tags.length - 3} more
                                                </Badge>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                isOpen={isDeleteDialogOpen}
                onClose={() => setIsDeleteDialogOpen(false)}
                onConfirm={() => {
                    confirmDelete();
                    setIsDeleteDialogOpen(false);
                }}
                title="Delete Note"
                description="Are you sure you want to delete this note? This action cannot be undone."
            />
        </AppLayout>
    );
}
