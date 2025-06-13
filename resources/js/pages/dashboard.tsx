import { Head, Link, useForm, router } from '@inertiajs/react';
import { FileText, Plus, Search, Tag, Trash2, Edit3, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState, useEffect } from 'react';

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

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedNotes {
    data: Note[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

interface DashboardProps {
    notes: PaginatedNotes;
    filters: {
        search: string;
    };
}

export default function Dashboard({ notes, filters }: DashboardProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const { delete: deleteNote } = useForm();
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [noteToDelete, setNoteToDelete] = useState<number | null>(null);

    // Debounced search effect
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchTerm !== filters.search) {
                router.get('/dashboard', 
                    { search: searchTerm }, 
                    { 
                        preserveState: true,
                        replace: true,
                        only: ['notes', 'filters']
                    }
                );
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [searchTerm, filters.search]);

    const openDeleteDialog = (noteId: number) => {
        setNoteToDelete(noteId);
        setIsDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (noteToDelete) {
            deleteNote(route('notes.destroy', noteToDelete), {
                preserveState: true,
                onSuccess: () => {
                    setIsDeleteDialogOpen(false);
                    setNoteToDelete(null);
                }
            });
        }
    };

    const handlePageChange = (url: string) => {
        if (url) {
            router.visit(url, {
                preserveState: true,
                only: ['notes']
            });
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

                {/* Results Summary */}
                {notes.total > 0 && (
                    <div className="text-sm text-muted-foreground">
                        Showing {notes.from} to {notes.to} of {notes.total} notes
                        {filters.search && ` for "${filters.search}"`}
                    </div>
                )}

                {/* Notes Grid */}
                {notes.data.length === 0 ? (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 className="mt-4 text-lg font-semibold">No notes found</h3>
                            <p className="mt-2 text-muted-foreground">
                                {filters.search
                                    ? 'Try adjusting your search terms'
                                    : 'Get started by creating your first note'}
                            </p>
                            {!filters.search && (
                                <Link href={route('notes.create')} className="mt-4 inline-block">
                                    <Button>Create your first note</Button>
                                </Link>
                            )}
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {notes.data.map((note) => (
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

                        {/* Pagination */}
                        {notes.last_page > 1 && (
                            <div className="flex items-center justify-between border-t pt-4">
                                <div className="text-sm text-muted-foreground">
                                    Page {notes.current_page} of {notes.last_page}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const prevLink = notes.links.find(link => link.label === '&laquo; Previous');
                                            if (prevLink?.url) handlePageChange(prevLink.url);
                                        }}
                                        disabled={notes.current_page === 1}
                                        className="gap-1"
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Previous
                                    </Button>
                                    
                                    {/* Page Numbers */}
                                    <div className="flex gap-1">
                                        {notes.links
                                            .filter(link => link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;')
                                            .map((link, index) => (
                                                <Button
                                                    key={index}
                                                    variant={link.active ? "default" : "outline"}
                                                    size="sm"
                                                    onClick={() => link.url && handlePageChange(link.url)}
                                                    disabled={!link.url}
                                                    className="min-w-[40px]"
                                                >
                                                    {link.label}
                                                </Button>
                                            ))
                                        }
                                    </div>

                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const nextLink = notes.links.find(link => link.label === 'Next &raquo;');
                                            if (nextLink?.url) handlePageChange(nextLink.url);
                                        }}
                                        disabled={notes.current_page === notes.last_page}
                                        className="gap-1"
                                    >
                                        Next
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                isOpen={isDeleteDialogOpen}
                onClose={() => setIsDeleteDialogOpen(false)}
                onConfirm={confirmDelete}
                title="Delete Note"
                description="Are you sure you want to delete this note? This action cannot be undone."
            />
        </AppLayout>
    );
}
