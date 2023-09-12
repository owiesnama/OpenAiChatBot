<?php

namespace App\Http\Controllers;

use App\Actions\EmbeddingBuilder;
use App\Models\Embedding;
use App\Models\EmbeddingsCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Spatie\PdfToText\Pdf;

class EmbeddingsController extends Controller
{
    public function index()
    {
        return inertia('Embeddings');
    }

    public function store()
    {
        $attributes = request()->validate([
            'files' => 'array',
            'files.*' => File::types(['pdf', 'txt']),
        ]);

        collect($attributes["files"])->each(function ($file) {
            $path = Storage::putFile('embeddings', $file);

            $collection = EmbeddingsCollection::create([
                'name' => $path,
                'meta_data' => collect()
            ]);
            if ($file->extension() == "pdf") {
                $text = (new Pdf("/usr/local/bin/pdftotext"))
                    ->setPdf($path)
                    ->text();
            } else {
                $text = Storage::get($path);
            }

            collect(
                tokenize($text)
            )
                ->chunk(200)
                ->each(
                    fn ($token) => Embedding::create([
                        'embeddings_collection_id' => $collection->id,
                        'text' => $token->implode("\n"),
                        'embedding' => json_encode(EmbeddingBuilder::query($token->implode("\n")))
                    ])
                );
        });
        return response("Done");
    }
}
