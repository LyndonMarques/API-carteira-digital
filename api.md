1. O Novo PHP (Descontaminando o Mindset)
   ├── Tipagem Estrita (Strict Types, Union/Intersection Types)
   ├── Enums, Atributos e Constructor Property Promotion
   └── Readonly Classes e Imutabilidade (Evitando efeitos colaterais)

2. Arquitetura Laravel de Alto Nível (Laravel 11.x)
   ├── Service Container e Injeção de Dependência Avançada
   ├── Segregação de Responsabilidades: Form Requests -> DTOs -> Actions
   └── API Resources e Padronização de Contratos JSON (Tratamento global de exceções)

3. Concorrência e Estado Distribuído
   ├── Eloquent Avançado (Database Transactions, Pessimistic/Optimistic Locking)
   └── Redis como Fonte de Verdade Transitória (Atomic Locks, Rate Limiting)

4. Orquestração Assíncrona (RabbitMQ)
   ├── Design de Filas: Produtores, Consumidores e Exchanges
   ├── Idempotência (Garantir que processar a mesma mensagem duas vezes não duplique a transação)
   └── Estratégias de Falha: Dead Letter Queues (DLQ) e Retry Patterns

5. Infraestrutura Implacável e CI/CD
   ├── Docker Multi-stage Builds (Imagens otimizadas e seguras para produção)
   ├── Pipelines de CI: Análise estática obrigatória (PHPStan Nível 8+, Pest para Testes)
   └── Kubernetes (K8s): Deployments, HPA (Autoscaling), Liveness/Readiness Probes
