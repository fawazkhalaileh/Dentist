<?php

namespace App\Enums;

enum TreatmentEventType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case NoteAdded = 'note_added';
    case AttachmentAdded = 'attachment_added';
}
