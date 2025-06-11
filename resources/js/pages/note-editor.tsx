import { Head, useForm } from '@inertiajs/react';
import { Save, Sparkles, FileText, Tags, Loader2, ChevronUp, ChevronDown } from 'lucide-react';
import { useEffect, useState, useRef } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Note, type NoteFormData } from '@/types/notes';

interface NoteEditorProps {
    note?: Note;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notes', href: '/dashboard' },
];

export default function NoteEditor({ note }: NoteEditorProps) {
    const [aiLoading, setAiLoading] = useState(false);
    const [aiResult, setAiResult] = useState('');
    const [selectedText, setSelectedText] = useState('');
    const [autoSaveTimeout, setAutoSaveTimeout] = useState<NodeJS.Timeout | null>(null);
    const [isAiPanelOpen, setIsAiPanelOpen] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [tagInput, setTagInput] = useState(note?.tags?.join(', ') || '');

    const { data, setData, post, put, processing, errors } = useForm<NoteFormData>({
        title: note?.title || '',
        content: note?.content || '',
        tags: note?.tags || [],
    });

    const isEditing = !!note;

    // Auto-save functionality
    useEffect(() => {
        if (isEditing && (data.title || data.content)) {
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }
            
            const timeout = setTimeout(() => {
                put(route('notes.update', note.id), {
                    preserveScroll: true,
                    preserveState: true,
                });
                setAutoSaveTimeout(null);
            }, 2000);

            setAutoSaveTimeout(timeout);
        }

        return () => {
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }
        };
    }, [data.title, data.content]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (isEditing) {
            put(route('notes.update', note.id));
        } else {
            post(route('notes.store'));
        }
    };

    const handleAIEnhancement = async (action: 'summarize' | 'improve' | 'generate_tags') => {
        const contentToEnhance = selectedText || data.content;
        
        if (!contentToEnhance.trim()) {
            alert('Please add some content or select text to enhance.');
            return;
        }

        setAiLoading(true);
        setAiResult('');

        try {
            const response = await fetch(route('ai.enhance'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    content: contentToEnhance,
                    action,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to enhance content');
            }

            // Check if response is streaming
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/plain')) {
                // Handle streaming response
                const reader = response.body?.getReader();
                const decoder = new TextDecoder();

                if (!reader) {
                    throw new Error('Failed to get response reader');
                }

                let buffer = '';
                
                while (true) {
                    const { done, value } = await reader.read();
                    
                    if (done) {
                        break;
                    }

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    
                    // Keep the last incomplete line in buffer
                    buffer = lines.pop() || '';
                    
                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = line.slice(6);
                            
                            if (data === '[DONE]') {
                                setAiLoading(false);
                                return;
                            }
                            
                            try {
                                const parsed = JSON.parse(data);
                                if (parsed.content) {
                                    setAiResult(prev => prev + parsed.content);
                                } else if (parsed.error) {
                                    setAiResult(parsed.error);
                                    setAiLoading(false);
                                    return;
                                }
                            } catch (e) {
                                // Skip malformed JSON
                                continue;
                            }
                        }
                    }
                }
            } else {
                // Handle non-streaming response (fallback)
                const data = await response.json();
                
                if (data.error) {
                    setAiResult(data.error);
                } else if (data.result) {
                    setAiResult(data.result);
                }
            }
        } catch (error) {
            console.error('AI Enhancement Error:', error);
            setAiResult('Failed to enhance content. Please try again.');
        } finally {
            setAiLoading(false);
        }
    };

    const handleTextSelection = () => {
        if (textareaRef.current) {
            const start = textareaRef.current.selectionStart;
            const end = textareaRef.current.selectionEnd;
            const selected = data.content.substring(start, end);
            setSelectedText(selected);
        }
    };

    const handleTagInput = (value: string) => {
        setTagInput(value);
        const tags = value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        setData('tags', tags);
    };

    const breadcrumbsWithTitle = [
        ...breadcrumbs,
        { title: isEditing ? note.title : 'New Note', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbsWithTitle}>
            <Head title={isEditing ? `Edit: ${note.title}` : 'New Note'} />
            
            <div className="flex h-full flex-1 flex-col lg:flex-row gap-4 lg:gap-6 p-4 lg:p-6">
                {/* Main Editor */}
                <div className="flex-1 space-y-4 lg:space-y-6 min-w-0">
                    <form onSubmit={handleSubmit} className="space-y-4 lg:space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <h1 className="text-xl lg:text-2xl font-bold">
                                {isEditing ? 'Edit Note' : 'Create New Note'}
                            </h1>
                            <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                {processing ? (
                                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                ) : (
                                    <Save className="h-4 w-4 mr-2" />
                                )}
                                {isEditing ? 'Update' : 'Save'}
                            </Button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Enter note title..."
                                    className="text-base lg:text-lg"
                                />
                                {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                            </div>

                            <div>
                                <Label htmlFor="content">Content</Label>
                                <Textarea
                                    ref={textareaRef}
                                    id="content"
                                    value={data.content}
                                    onChange={(e) => setData('content', e.target.value)}
                                    onMouseUp={handleTextSelection}
                                    onKeyUp={handleTextSelection}
                                    placeholder="Start writing your note..."
                                    className="min-h-[300px] lg:min-h-[400px] text-sm lg:text-base leading-relaxed"
                                />
                                {errors.content && <p className="text-sm text-red-600">{errors.content}</p>}
                            </div>

                            <div>
                                <Label htmlFor="tags">Tags (comma-separated)</Label>
                                <Input
                                    id="tags"
                                    value={tagInput}
                                    onChange={(e) => handleTagInput(e.target.value)}
                                    placeholder="Enter tags separated by commas..."
                                />
                                {data.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 mt-2">
                                        {data.tags.map((tag, index) => (
                                            <Badge key={index} variant="secondary" className="text-xs">
                                                <Tags className="h-3 w-3 mr-1" />
                                                {tag}
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </form>
                </div>

                {/* AI Enhancement Panel - Mobile Collapsible, Desktop Sidebar */}
                <div className="lg:w-80 lg:flex-shrink-0">
                    {/* Mobile: Collapsible AI Panel */}
                    <div className="lg:hidden">
                        <Collapsible open={isAiPanelOpen} onOpenChange={setIsAiPanelOpen}>
                            <CollapsibleTrigger asChild>
                                <Button variant="outline" className="w-full justify-between mb-4">
                                    <span className="flex items-center gap-2">
                                        <Sparkles className="h-4 w-4" />
                                        AI Enhancement
                                    </span>
                                    {isAiPanelOpen ? (
                                        <ChevronUp className="h-4 w-4" />
                                    ) : (
                                        <ChevronDown className="h-4 w-4" />
                                    )}
                                </Button>
                            </CollapsibleTrigger>
                            <CollapsibleContent className="space-y-4">
                                <AIEnhancementContent
                                    selectedText={selectedText}
                                    aiLoading={aiLoading}
                                    aiResult={aiResult}
                                    handleAIEnhancement={handleAIEnhancement}
                                    isEditing={isEditing}
                                    note={note}
                                    autoSaveTimeout={autoSaveTimeout}
                                />
                            </CollapsibleContent>
                        </Collapsible>
                    </div>

                    {/* Desktop: Fixed Sidebar */}
                    <div className="hidden lg:block space-y-4">
                        <AIEnhancementContent
                            selectedText={selectedText}
                            aiLoading={aiLoading}
                            aiResult={aiResult}
                            handleAIEnhancement={handleAIEnhancement}
                            isEditing={isEditing}
                            note={note}
                            autoSaveTimeout={autoSaveTimeout}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

// Extracted AI Enhancement Content Component
interface AIEnhancementContentProps {
    selectedText: string;
    aiLoading: boolean;
    aiResult: string;
    handleAIEnhancement: (action: 'summarize' | 'improve' | 'generate_tags') => void;
    isEditing: boolean;
    note?: Note;
    autoSaveTimeout: NodeJS.Timeout | null;
}

function AIEnhancementContent({
    selectedText,
    aiLoading,
    aiResult,
    handleAIEnhancement,
    isEditing,
    note,
    autoSaveTimeout,
}: AIEnhancementContentProps) {
    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base lg:text-lg">
                        <Sparkles className="h-4 w-4 lg:h-5 lg:w-5" />
                        AI Enhancement
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {selectedText && (
                        <div className="p-3 bg-muted rounded-md">
                            <p className="text-sm font-medium mb-1">Selected Text:</p>
                            <p className="text-xs lg:text-sm text-muted-foreground break-words">
                                {selectedText}
                            </p>
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Button
                            onClick={() => handleAIEnhancement('summarize')}
                            disabled={aiLoading}
                            variant="outline"
                            className="justify-start text-sm"
                            size="sm"
                        >
                            <FileText className="h-4 w-4 mr-2" />
                            Summarize
                        </Button>
                        <Button
                            onClick={() => handleAIEnhancement('improve')}
                            disabled={aiLoading}
                            variant="outline"
                            className="justify-start text-sm"
                            size="sm"
                        >
                            <Sparkles className="h-4 w-4 mr-2" />
                            Improve Writing
                        </Button>
                        <Button
                            onClick={() => handleAIEnhancement('generate_tags')}
                            disabled={aiLoading}
                            variant="outline"
                            className="justify-start text-sm"
                            size="sm"
                        >
                            <Tags className="h-4 w-4 mr-2" />
                            Generate Tags
                        </Button>
                    </div>

                    {aiLoading && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            AI is working...
                        </div>
                    )}

                    {aiResult && (
                        <div className="space-y-3">
                            <Separator />
                            <div>
                                <Label className="text-sm font-medium">AI Result:</Label>
                                <div className="mt-2 p-3 bg-muted rounded-md text-xs lg:text-sm break-words">
                                    {aiResult}
                                </div>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>

            {isEditing && note && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm">Note Info</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-xs lg:text-sm">
                        <div>
                            <span className="font-medium">Created:</span>
                            <br />
                            <span className="break-words">{note.created_at}</span>
                        </div>
                        <div>
                            <span className="font-medium">Last updated:</span>
                            <br />
                            <span className="break-words">{note.updated_at}</span>
                        </div>
                        {autoSaveTimeout && (
                            <div className="text-muted-foreground text-xs">
                                Auto-saving...
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}
        </>
    );
}