<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $batchId;
    public int $processedRows;
    public int $totalRows;

    /**
     * Create a new event instance.
     */
    public function __construct(int $batchId, int $processedRows, int $totalRows)
    {
        $this->batchId = $batchId;
        $this->processedRows = $processedRows;
        $this->totalRows = $totalRows;
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'batchId' => $this->batchId,
            'processedRows' => $this->processedRows,
            'totalRows' => $this->totalRows,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('import.' . $this->batchId),
        ];
    }
}
