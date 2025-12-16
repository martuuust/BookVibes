<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Book;
use App\Models\Playlist;
use App\Models\Character;

class ApiController extends Controller
{
    public function getBooks()
    {
        $books = Book::all();
        $this->json(['data' => $books]);
    }

    public function getBookDetails(Request $request)
    {
        // manually get ID from query since my router is basic
        $id = $request->getBody()['id'] ?? null;
        
        if (!$id) {
            $this->json(['error' => 'Missing ID'], 400);
            return;
        }

        $book = Book::find($id);
        if (!$book) {
            $this->json(['error' => 'Book not found'], 404);
            return;
        }

        $this->json(['data' => $book]);
    }

    public function getPlaylist(Request $request)
    {
        $id = $request->getBody()['id'] ?? null;
        $playlist = Playlist::getByBookId($id);
        
        if (!$playlist) {
            $this->json(['error' => 'Playlist not found'], 404);
            return;
        }

        $this->json(['data' => $playlist]);
    }

    public function getCharacters(Request $request)
    {
        $id = $request->getBody()['id'] ?? null;
        $characters = Character::getByBookId($id);
        
        $this->json(['data' => $characters]);
    }
}
