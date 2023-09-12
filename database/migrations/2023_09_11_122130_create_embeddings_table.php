<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\EmbeddingsCollection;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->longText('text');
            $table->foreignIdFor(EmbeddingsCollection::class)->onDelete('cascade');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE embeddings ADD embedding vector;");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
