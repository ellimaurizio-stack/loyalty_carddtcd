<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Products/Index', [
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'ean_code' => 'required|string|unique:products,ean_code',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:5120', // max 5MB
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);
        return redirect()->back()->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'ean_code' => 'required|string|unique:products,ean_code,' . $product->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:5120', // max 5MB
        ]);

        if ($request->hasFile('image')) {
            // Elimina la vecchia immagine se esiste? Per ora sovrascriviamo solo il path
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);
        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=template_prodotti.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $columns = ['name', 'category', 'ean_code', 'price', 'stock', 'image_filename'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['Prodotto Esempio', 'Categoria Esempio', '1234567890123', '10.50', '50', 'nutella.jpg']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->path(), 'r');
        $header = fgetcsv($handle);

        if (!$header || count($header) < 5) {
            return redirect()->back()->with('error', 'Il file CSV non ha un formato valido.');
        }

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) continue;
            
            // Expected: name, category, ean_code, price, stock, image_filename
            $ean = trim($row[2]);
            if (empty($ean)) continue;

            $data = [
                'name' => trim($row[0]),
                'category' => trim($row[1]),
                'price' => (float) str_replace(',', '.', trim($row[3])),
                'stock' => (int) trim($row[4])
            ];

            if (isset($row[5]) && !empty(trim($row[5]))) {
                // Prepend products/ since that's the folder we extract the zip into
                $data['image_path'] = 'products/' . trim($row[5]);
            }

            Product::updateOrCreate(
                ['ean_code' => $ean],
                $data
            );
            $imported++;
        }
        fclose($handle);

        return redirect()->back()->with('success', "Importazione completata: $imported prodotti processati.");
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0'
        ]);

        $product->update(['stock' => $validated['stock']]);
        return redirect()->back()->with('success', 'Giacenza aggiornata.');
    }

    public function importZip(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:51200' // 50MB max
        ]);

        $zipPath = $request->file('zip_file')->path();
        $zip = new \ZipArchive;
        
        if ($zip->open($zipPath) === TRUE) {
            $extractPath = storage_path('app/public/products');
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            
            // Extract the files directly to the products directory
            $extracted = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // Skip directories or hidden files
                if (substr($filename, -1) == '/' || substr(basename($filename), 0, 1) == '.') continue;
                
                // We flatten the zip structure (ignoring folders inside zip)
                $content = $zip->getFromIndex($i);
                $destPath = $extractPath . '/' . basename($filename);
                file_put_contents($destPath, $content);
                $extracted++;
            }
            $zip->close();

            return redirect()->back()->with('success', "Archivio estratto: $extracted immagini caricate con successo.");
        } else {
            return redirect()->back()->with('error', 'Impossibile aprire il file ZIP.');
        }
    }
}
