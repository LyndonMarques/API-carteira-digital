<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTransferNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $transferId)
    {
        $this->transferId = $transferId;
    }

    public function handle(): void
    {
        Log::info("Worker: Iniciando notificação pesada para a transferência ID: {$this->transferId}");
        
        // Simula o tempo de rede de uma requisição lenta para AWS SES, Firebase ou Webhook externo
        sleep(3); 
        
        Log::info("Worker: Notificação da transferência {$this->transferId} processada com sucesso.");
    }
}