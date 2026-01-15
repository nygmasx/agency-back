<?php

namespace App\Enums;

enum CollaboratorRole: string
{
    case Viewer = 'viewer';
    case Commenter = 'commenter';
    case Editor = 'editor';

    public function permissions(): array
    {
        return match ($this) {
            self::Viewer => ['view'],
            self::Commenter => ['view', 'comment'],
            self::Editor => ['view', 'comment', 'edit'],
        };
    }

    public function canView(): bool
    {
        return true;
    }

    public function canComment(): bool
    {
        return in_array($this, [self::Commenter, self::Editor]);
    }

    public function canEdit(): bool
    {
        return $this === self::Editor;
    }

    public function label(): string
    {
        return match ($this) {
            self::Viewer => 'Lecteur',
            self::Commenter => 'Commentateur',
            self::Editor => 'Éditeur',
        };
    }
}
