<?php

namespace Database\Seeders;

use App\Enums\LoanType;
use App\Models\Author;
use App\Models\Category;
use App\Models\Copy;
use App\Models\Edition;
use App\Models\Location;
use App\Models\Publisher;
use App\Models\Tag;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogueSeeder extends Seeder
{
    /**
     * [title, [authors], publisher, year, pages, language, format, categorySlug, [tags], copies, isbn13, loanType]
     *
     * @var array<int, array>
     */
    private array $books = [
        ['The Hobbit', ['J. R. R. Tolkien'], 'HarperCollins', 1997, 310, 'en', 'paperback', 'fantasy', ['adventure', 'middle-earth'], 3, '9780261103283'],
        ['Dune', ['Frank Herbert'], 'Ace Books', 2005, 704, 'en', 'paperback', 'sci-fi', ['space opera', 'ecology'], 4, '9780441013593'],
        ['1984', ['George Orwell'], 'Signet Classic', 1961, 328, 'en', 'paperback', 'literary', ['dystopia', 'classic'], 4, '9780451524935'],
        ['Brave New World', ['Aldous Huxley'], 'Harper Perennial', 2006, 288, 'en', 'paperback', 'sci-fi', ['dystopia', 'classic'], 2, '9780060850524'],
        ['Foundation', ['Isaac Asimov'], 'Bantam', 1991, 296, 'en', 'paperback', 'sci-fi', ['space opera', 'classic'], 3, '9780553293357'],
        ['The Name of the Wind', ['Patrick Rothfuss'], 'DAW', 2008, 662, 'en', 'paperback', 'fantasy', ['high fantasy'], 2, '9780756405892'],
        ['The Martian', ['Andy Weir'], 'Crown', 2014, 384, 'en', 'hardcover', 'sci-fi', ['space', 'survival'], 3, '9780553418026'],
        ['Project Hail Mary', ['Andy Weir'], 'Ballantine', 2021, 496, 'en', 'hardcover', 'sci-fi', ['space', 'first contact'], 3, '9780593135204'],
        ['Ready Player One', ['Ernest Cline'], 'Crown', 2011, 374, 'en', 'hardcover', 'sci-fi', ['virtual reality', 'gaming'], 2, '9780307887443'],
        ['Neuromancer', ['William Gibson'], 'Ace Books', 1984, 271, 'en', 'paperback', 'sci-fi', ['cyberpunk', 'classic'], 2, '9780441569595'],
        ['A Brief History of Time', ['Stephen Hawking'], 'Bantam', 1998, 212, 'en', 'paperback', 'physics', ['cosmology', 'popular science'], 2, '9780553380163'],
        ['Cosmos', ['Carl Sagan'], 'Ballantine', 2013, 396, 'en', 'paperback', 'science', ['astronomy', 'popular science'], 2, '9780345539435'],
        ['Sapiens: A Brief History of Humankind', ['Yuval Noah Harari'], 'Harper', 2018, 464, 'en', 'paperback', 'history', ['anthropology', 'big ideas'], 3, '9780062316110'],
        ['Guns, Germs, and Steel', ['Jared Diamond'], 'W. W. Norton', 2005, 528, 'en', 'paperback', 'history', ['civilisation', 'anthropology'], 2, '9780393354324'],
        ['Homo Deus', ['Yuval Noah Harari'], 'Harper', 2017, 464, 'en', 'paperback', 'history', ['future', 'big ideas'], 2, '9780062464316'],
        ['The Selfish Gene', ['Richard Dawkins'], 'Oxford University Press', 2016, 496, 'en', 'paperback', 'biology', ['evolution', 'science'], 2, '9780198788607'],
        ['A Short History of Nearly Everything', ['Bill Bryson'], 'Broadway', 2004, 544, 'en', 'paperback', 'science', ['popular science'], 3, '9780767908184'],
        ['The Elegant Universe', ['Brian Greene'], 'Vintage', 2000, 448, 'en', 'paperback', 'physics', ['string theory', 'popular science'], 2, '9780375708114'],
        ['Astrophysics for People in a Hurry', ['Neil deGrasse Tyson'], 'W. W. Norton', 2017, 224, 'en', 'hardcover', 'physics', ['astrophysics', 'popular science'], 3, '9780393609394'],
        ['The Code Book', ['Simon Singh'], 'Anchor', 2000, 416, 'en', 'paperback', 'computer', ['cryptography', 'history'], 2, '9780385495325'],
        ['Clean Code', ['Robert C. Martin'], 'Prentice Hall', 2008, 464, 'en', 'paperback', 'computer', ['software craft'], 2, '9780132350884'],
        ['The Pragmatic Programmer', ['Andrew Hunt', 'David Thomas'], 'Addison-Wesley', 2019, 352, 'en', 'hardcover', 'computer', ['software craft'], 2, '9780135957059'],
        ['The Design of Everyday Things', ['Don Norman'], 'Basic Books', 2013, 368, 'en', 'paperback', 'society', ['design', 'usability'], 2, '9780465050659'],
        ['Thinking, Fast and Slow', ['Daniel Kahneman'], 'Farrar, Straus and Giroux', 2011, 499, 'en', 'paperback', 'society', ['psychology', 'behaviour'], 3, '9780374533557'],
        ['Freakonomics', ['Steven D. Levitt', 'Stephen J. Dubner'], 'William Morrow', 2005, 336, 'en', 'paperback', 'society', ['economics', 'psychology'], 2, '9780060731335'],
        ['Educated', ['Tara Westover'], 'Random House', 2018, 334, 'en', 'hardcover', 'biography', ['memoir', 'education'], 3, '9780399590504'],
        ['Steve Jobs', ['Walter Isaacson'], 'Simon & Schuster', 2011, 656, 'en', 'hardcover', 'biography', ['technology', 'leadership'], 2, '9781451648539'],
        ['Becoming', ['Michelle Obama'], 'Crown', 2018, 448, 'en', 'hardcover', 'biography', ['memoir'], 2, '9781524763138'],
        ['Long Walk to Freedom', ['Nelson Mandela'], 'Back Bay Books', 1995, 656, 'en', 'paperback', 'biography', ['politics', 'memoir'], 1, '9780316548182'],
        ['The Diary of a Young Girl', ['Anne Frank'], 'Bantam', 1993, 288, 'en', 'paperback', 'history', ['memoir', 'world war ii'], 2, '9780553296983'],
        ['The Guns of August', ['Barbara W. Tuchman'], 'Ballantine', 1994, 640, 'en', 'paperback', 'history', ['world war i', 'military'], 2, '9780345386236'],
        ['A People\'s History of the United States', ['Howard Zinn'], 'Harper Perennial', 2015, 784, 'en', 'paperback', 'history', ['politics'], 1, '9780062397348'],
        ['The Girl with the Dragon Tattoo', ['Stieg Larsson'], 'Vintage', 2009, 590, 'en', 'paperback', 'mystery', ['crime', 'sweden'], 3, '9780307454546'],
        ['Gone Girl', ['Gillian Flynn'], 'Crown', 2012, 432, 'en', 'hardcover', 'mystery', ['thriller', 'psychological'], 3, '9780307588371'],
        ['The Silent Patient', ['Alex Michaelides'], 'Celadon', 2019, 336, 'en', 'hardcover', 'mystery', ['thriller', 'psychological'], 3, '9781250301697'],
        ['And Then There Were None', ['Agatha Christie'], 'Harper', 2011, 300, 'en', 'paperback', 'mystery', ['crime', 'classic'], 2, '9780062073488'],
        ['Pride and Prejudice', ['Jane Austen'], 'Penguin Classics', 2002, 480, 'en', 'paperback', 'literary', ['romance', 'classic'], 3, '9780141439518'],
        ['One Hundred Years of Solitude', ['Gabriel García Márquez'], 'Harper Perennial', 2006, 417, 'en', 'paperback', 'literary', ['magical realism', 'latin america'], 3, '9780060883287'],
        ['The Great Gatsby', ['F. Scott Fitzgerald'], 'Scribner', 2004, 180, 'en', 'paperback', 'literary', ['classic', 'american'], 4, '9780743273565'],
        ['To Kill a Mockingbird', ['Harper Lee'], 'Harper Perennial', 2006, 336, 'en', 'paperback', 'literary', ['classic', 'american'], 4, '9780061120084'],
        ['The Fault in Our Stars', ['John Green'], 'Dutton', 2012, 313, 'en', 'hardcover', 'children', ['young adult', 'romance'], 2, '9780525478812'],
        ['The Very Hungry Caterpillar', ['Eric Carle'], 'Philomel', 1994, 26, 'en', 'hardcover', 'children', ['picture book'], 3, '9780399226908'],
        ['Harry Potter and the Sorcerer\'s Stone', ['J. K. Rowling'], 'Scholastic', 1999, 309, 'en', 'paperback', 'children', ['fantasy', 'young adult'], 4, '9780439708180'],
        ['The Lion, the Witch and the Wardrobe', ['C. S. Lewis'], 'HarperCollins', 2002, 206, 'en', 'paperback', 'children', ['fantasy', 'classic'], 3, '9780064404990'],
        ['Merriam-Webster\'s Collegiate Dictionary', ['Merriam-Webster'], 'Merriam-Webster', 2020, 1664, 'en', 'hardcover', 'reference', ['dictionary', 'reference'], 1, '9780877798095', 'reference'],
        ['National Geographic — 2024 back issues', ['National Geographic'], 'National Geographic', 2024, 100, 'en', 'magazine', 'reference', ['magazine', 'nature'], 4, '9780789023456', 'periodical'],
    ];

    public function run(): void
    {
        $shelves = Location::where('type', 'shelf')->orderBy('id')->get();
        $actor = User::where('email', 'admin@betsefer.local')->first();

        foreach ($this->books as $i => $book) {
            [$title, $authors, $publisherName, $year, $pages, $language, $format, $categorySlug, $tags, $copyCount, $isbn13] = $book;
            $loanType = $book[11] ?? 'general';

            $loanTypeEnum = match ($loanType) {
                'reference' => LoanType::Reference,
                'periodical' => LoanType::Periodical,
                default => LoanType::General,
            };

            $edition = Edition::create([
                'ulid' => (string) Str::ulid(),
                'isbn_13' => $isbn13,
                'title' => $title,
                'publisher_id' => $this->publisher($publisherName)->id,
                'category_id' => Category::where('slug', $categorySlug)->first()?->id,
                'published_year' => $year,
                'language' => $language,
                'page_count' => $pages,
                'format' => $format,
                'loan_type' => $loanTypeEnum,
                'special_material' => false,
                'loan_restricted_default' => $loanTypeEnum === LoanType::Reference,
                'metadata_source' => 'manual',
                'created_by_id' => $actor?->id,
                'summary' => null,
            ]);

            foreach ($authors as $position => $authorName) {
                $author = $this->author($authorName);
                $edition->authors()->attach($author->id, ['role' => 'author', 'position' => $position]);
            }

            foreach ($tags as $tagName) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => $tagName, 'source' => 'import'],
                );
                $edition->tags()->attach($tag->id);
            }

            for ($j = 0; $j < $copyCount; $j++) {
                $shelf = $shelves[($i + $j) % max(1, $shelves->count())];

                Copy::create([
                    'ulid' => (string) Str::ulid(),
                    'code' => CrockfordCode::withPrefix('BS'),
                    'edition_id' => $edition->id,
                    'location_id' => $shelf?->id,
                    'status' => 'available',
                    'condition' => match ($j) {
                        0 => 'new',
                        1 => 'good',
                        default => 'good',
                    },
                    'loan_restricted' => null,
                    'acquisition_date' => now()->subMonths(rand(1, 18))->toDateString(),
                    'status_changed_at' => now(),
                ]);
            }
        }
    }

    private function publisher(string $name): Publisher
    {
        return Publisher::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }

    private function author(string $name): Author
    {
        $slug = Str::slug($name);
        $author = Author::where('slug', $slug)->first();

        if ($author === null) {
            $author = Author::create([
                'ulid' => (string) Str::ulid(),
                'name' => $name,
                'slug' => $slug,
            ]);
        }

        return $author;
    }
}
