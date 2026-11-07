<?php
declare(strict_types=1);

function ensure_extra_catalog(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $find = db()->prepare('SELECT id, vendor_id FROM products WHERE slug = ? LIMIT 1');
    $insert = db()->prepare('INSERT INTO products
        (category_id, vendor_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $setVendor = db()->prepare('UPDATE products SET vendor_id = ? WHERE id = ? AND vendor_id IS NULL');

    foreach (extra_catalog_products() as $product) {
        $find->execute([$product['slug']]);
        $row = $find->fetch();
        $vendorId = catalog_vendor_id((int) $product['category_id']);
        if (!$row) {
            $insert->execute([
                $product['category_id'],
                $vendorId,
                $product['name'],
                $product['slug'],
                $product['short_desc'],
                $product['description'],
                $product['emoji'],
                $product['unit_label'],
                $product['price'],
                $product['mrp'],
                $product['stock'],
                $product['sku'],
                $product['is_bestseller'],
                $product['is_trending'],
                $product['is_new'],
                'none',
            ]);
            $productId = (int) db()->lastInsertId();
        } else {
            $productId = (int) $row['id'];
            if ($vendorId && empty($row['vendor_id'])) {
                $setVendor->execute([$vendorId, $productId]);
            }
        }
        catalog_place_stock($productId, (int) $product['stock']);
    }
}

function catalog_vendor_id(int $categoryId): ?int
{
    $email = match (true) {
        in_array($categoryId, [1, 7], true) => 'vendor.toys@storepreschoolapp.in',
        in_array($categoryId, [2, 3], true) => 'vendor.books@storepreschoolapp.in',
        default => 'vendor.kits@storepreschoolapp.in',
    };
    $stmt = db()->prepare('SELECT id FROM vendors WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

function catalog_place_stock(int $productId, int $qty): void
{
    if ($productId <= 0 || $qty < 0) {
        return;
    }
    $exists = db()->prepare('SELECT 1 FROM store_stock WHERE product_id = ? LIMIT 1');
    $exists->execute([$productId]);
    if ($exists->fetch()) {
        return;
    }
    $stores = db()->query('SELECT id, code FROM dark_stores WHERE is_active = 1')->fetchAll();
    if (!$stores) {
        return;
    }
    $byCode = [];
    foreach ($stores as $store) {
        $byCode[(string) $store['code']] = (int) $store['id'];
    }
    $insert = db()->prepare('INSERT INTO store_stock (dark_store_id, product_id, quantity) VALUES (?, ?, ?)');
    $pune = $byCode['PUNE-CAMP'] ?? null;
    $kochi = $byCode['KOCHI-LAKE'] ?? null;
    if ($pune && $kochi) {
        $kochiQty = $qty >= 4 ? 2 : 0;
        $insert->execute([$pune, $productId, $qty - $kochiQty]);
        $insert->execute([$kochi, $productId, $kochiQty]);
        return;
    }
    $only = $pune ?? $kochi ?? (int) $stores[0]['id'];
    $insert->execute([$only, $productId, $qty]);
}

function extra_catalog_products(): array
{
    return [
        ['category_id' => 1, 'name' => 'Animal Finger Puppet Set', 'slug' => 'animal-finger-puppets', 'short_desc' => 'Five soft puppets for circle-time stories.', 'description' => 'A dog, cat, bird, elephant and lion that fit a small finger. Teachers use them for stories, animal sounds and turn-taking.', 'emoji' => '🖐️', 'unit_label' => '5 puppets', 'price' => 249, 'mrp' => 349, 'stock' => 40, 'sku' => 'TOY-PUPPET-05', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],
        ['category_id' => 1, 'name' => 'Wooden Pull-Along Duck', 'slug' => 'wooden-pull-along-duck', 'short_desc' => 'A duck on wheels with a short pull string.', 'description' => 'Children pull it across the classroom floor. The wheels are quiet and the string is short so it stays in the play area.', 'emoji' => '🦆', 'unit_label' => '1 toy', 'price' => 399, 'mrp' => 549, 'stock' => 24, 'sku' => 'TOY-DUCK-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 1, 'name' => 'Stacking Ring Pyramid', 'slug' => 'stacking-ring-pyramid', 'short_desc' => 'Eight rings that stack from big to small.', 'description' => 'A first stacking toy for Nursery. The centre post keeps the rings in place and the colours are easy to name.', 'emoji' => '🔺', 'unit_label' => '8 rings', 'price' => 299, 'mrp' => 399, 'stock' => 36, 'sku' => 'TOY-RINGS-08', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],
        ['category_id' => 1, 'name' => 'Soft Activity Cube', 'slug' => 'soft-activity-cube', 'short_desc' => 'A fabric cube with crinkle, mirror and tags.', 'description' => 'Each side has a different touch: a mirror, a crinkle patch, a ribbon and a squeaker. It is light enough for the youngest class.', 'emoji' => '🎲', 'unit_label' => '1 cube', 'price' => 549, 'mrp' => 749, 'stock' => 20, 'sku' => 'TOY-ACTCUBE-01', 'is_bestseller' => 1, 'is_trending' => 0, 'is_new' => 0],

        ['category_id' => 2, 'name' => 'Colours and Shapes Picture Book', 'slug' => 'colours-shapes-picture-book', 'short_desc' => 'One colour or shape on each thick page.', 'description' => 'A board book for naming red, blue, circle and square. The pictures are large enough for a small group to see.', 'emoji' => '🌈', 'unit_label' => '1 book', 'price' => 179, 'mrp' => 249, 'stock' => 40, 'sku' => 'BOK-COLOR-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 2, 'name' => 'Good Habits Board Book', 'slug' => 'good-habits-board-book', 'short_desc' => 'Wash hands, share toys and tidy up.', 'description' => 'Four short pages on everyday class habits. Teachers read one page at the start of the day.', 'emoji' => '🧼', 'unit_label' => '1 book', 'price' => 199, 'mrp' => 279, 'stock' => 35, 'sku' => 'BOK-HABIT-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],
        ['category_id' => 2, 'name' => 'Hindi Varnamala Picture Book', 'slug' => 'hindi-varnamala-picture-book', 'short_desc' => 'Each Hindi letter with a familiar picture.', 'description' => 'A first Hindi alphabet book for circle time. Every letter sits next to an object children already know.', 'emoji' => '🔤', 'unit_label' => '1 book', 'price' => 219, 'mrp' => 299, 'stock' => 30, 'sku' => 'BOK-HINDI-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 2, 'name' => 'Bedtime Animal Stories', 'slug' => 'bedtime-animal-stories', 'short_desc' => 'Five very short animal stories.', 'description' => 'Each story ends in a few minutes, so it fits rest time or a send-home reading bag.', 'emoji' => '🌙', 'unit_label' => '1 book', 'price' => 249, 'mrp' => 349, 'stock' => 28, 'sku' => 'BOK-SLEEP-01', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],

        ['category_id' => 3, 'name' => 'Eraser and Sharpener Pack', 'slug' => 'eraser-sharpener-pack', 'short_desc' => 'Six soft erasers and six safe sharpeners.', 'description' => 'A class refill pack. The sharpeners have a small bin so shavings stay off the table.', 'emoji' => '🧽', 'unit_label' => '6 sets', 'price' => 79, 'mrp' => 119, 'stock' => 80, 'sku' => 'STA-ERASE-06', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 3, 'name' => 'Washable Sketch Pens', 'slug' => 'washable-sketch-pens', 'short_desc' => '12 bright pens that wash off skin.', 'description' => 'Felt tips for drawing and labelling charts. Marks on hands wash away with soap and water.', 'emoji' => '🖊️', 'unit_label' => '12 colours', 'price' => 129, 'mrp' => 179, 'stock' => 60, 'sku' => 'STA-SKETCH-12', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],
        ['category_id' => 3, 'name' => 'Two-Compartment Pencil Box', 'slug' => 'two-compartment-pencil-box', 'short_desc' => 'A box that keeps pencils and crayons apart.', 'description' => 'The lid clicks shut and the tray lifts out. It fits the preschool backpack pocket.', 'emoji' => '📦', 'unit_label' => '1 box', 'price' => 199, 'mrp' => 279, 'stock' => 40, 'sku' => 'STA-BOX-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 3, 'name' => 'A4 Drawing Book Pack', 'slug' => 'a4-drawing-book-pack', 'short_desc' => 'Three plain books for drawing class.', 'description' => 'Thick blank pages that take crayon and sketch pen without tearing. A simple pack for a term of art.', 'emoji' => '🖼️', 'unit_label' => '3 books', 'price' => 149, 'mrp' => 199, 'stock' => 50, 'sku' => 'STA-DRAW-03', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],

        ['category_id' => 4, 'name' => 'Watercolour Cake Set', 'slug' => 'watercolour-cake-set', 'short_desc' => '12 dry colour cakes and one brush.', 'description' => 'Children dip the brush and paint. The cakes stay in the tin, so the set is easier to store than bottles.', 'emoji' => '🎨', 'unit_label' => '12 colours', 'price' => 249, 'mrp' => 349, 'stock' => 30, 'sku' => 'ART-WATER-12', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 4, 'name' => 'Pom Pom and Googly Eye Pack', 'slug' => 'pom-pom-googly-eye-pack', 'short_desc' => 'Pom poms, eyes and glue dots for collage.', 'description' => 'A craft-hour pack for faces and animals. The pieces are large enough for little fingers to pick up.', 'emoji' => '👀', 'unit_label' => '1 pack', 'price' => 129, 'mrp' => 179, 'stock' => 45, 'sku' => 'ART-POM-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],
        ['category_id' => 4, 'name' => 'Child Art Apron 2 Pack', 'slug' => 'child-art-apron-2-pack', 'short_desc' => 'Two wipe-clean aprons with a front pocket.', 'description' => 'Sleeveless aprons that go on over the uniform. Paint wipes off and the pocket holds a brush.', 'emoji' => '👕', 'unit_label' => '2 aprons', 'price' => 299, 'mrp' => 399, 'stock' => 25, 'sku' => 'ART-APRON-02', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 4, 'name' => 'Sponge Stamp Shape Set', 'slug' => 'sponge-stamp-shape-set', 'short_desc' => 'Six shape stamps with easy handles.', 'description' => 'Circles, stars and triangles children press onto paper. The handles keep paint off their palms.', 'emoji' => '🔶', 'unit_label' => '6 stamps', 'price' => 199, 'mrp' => 279, 'stock' => 32, 'sku' => 'ART-STAMP-06', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],

        ['category_id' => 5, 'name' => 'Classroom Storage Bin Set', 'slug' => 'classroom-storage-bin-set', 'short_desc' => 'Six open bins for blocks, books and dolls.', 'description' => 'Low bins children can carry to the shelf. Labels fit on the front so the class can tidy by picture.', 'emoji' => '🧺', 'unit_label' => '6 bins', 'price' => 1599, 'mrp' => 1999, 'stock' => 10, 'sku' => 'FUR-BIN-06', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 5, 'name' => 'Teacher Story-Time Chair', 'slug' => 'teacher-story-time-chair', 'short_desc' => 'A low chair for reading to a circle.', 'description' => 'Sits at child height so the book faces the group. The frame is light enough to move to the mat.', 'emoji' => '🪑', 'unit_label' => '1 chair', 'price' => 1899, 'mrp' => 2499, 'stock' => 8, 'sku' => 'FUR-TCHAIR-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 5, 'name' => 'Eight-Cubbie Bag Rack', 'slug' => 'eight-cubbie-bag-rack', 'short_desc' => 'Eight open cubbies for bags and bottles.', 'description' => 'A wall-side rack for a small class. Each cubbie holds one backpack and has room for a name card.', 'emoji' => '🗄️', 'unit_label' => '8 cubbies', 'price' => 4999, 'mrp' => 6299, 'stock' => 6, 'sku' => 'FUR-CUBBY-08', 'is_bestseller' => 1, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 5, 'name' => 'Reading Corner Cushion Pair', 'slug' => 'reading-corner-cushion-pair', 'short_desc' => 'Two washable floor cushions.', 'description' => 'Soft seats for the book corner. Covers come off for washing after a term of sitting and snacking.', 'emoji' => '🛋️', 'unit_label' => '2 cushions', 'price' => 999, 'mrp' => 1299, 'stock' => 14, 'sku' => 'FUR-CUSH-02', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],

        ['category_id' => 6, 'name' => 'Number Cards 1 to 20', 'slug' => 'number-cards-1-to-20', 'short_desc' => 'One card for each number, with dots to count.', 'description' => 'Children match the numeral to the dots. The cards are rounded and large enough for the mat.', 'emoji' => '🔢', 'unit_label' => '20 cards', 'price' => 199, 'mrp' => 279, 'stock' => 40, 'sku' => 'EDU-NUM-20', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 6, 'name' => 'Lacing Beads Set', 'slug' => 'lacing-beads-set', 'short_desc' => 'Chunky beads and two laces.', 'description' => 'A fine-motor shelf activity. Beads are too big to swallow and the laces have a stiff tip.', 'emoji' => '🧵', 'unit_label' => '1 set', 'price' => 349, 'mrp' => 449, 'stock' => 28, 'sku' => 'EDU-LACE-01', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],
        ['category_id' => 6, 'name' => 'Weather and Seasons Chart', 'slug' => 'weather-seasons-chart', 'short_desc' => 'A wall chart with movable weather cards.', 'description' => 'The class pins today weather each morning: sun, cloud, rain or wind, plus the four seasons.', 'emoji' => '⛅', 'unit_label' => '1 chart', 'price' => 249, 'mrp' => 329, 'stock' => 22, 'sku' => 'EDU-WEATH-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 6, 'name' => 'Letter Tracing Board', 'slug' => 'letter-tracing-board', 'short_desc' => 'Grooved letters for finger tracing.', 'description' => 'Children trace uppercase letters with a finger before they write with a pencil. The board wipes clean.', 'emoji' => '✍️', 'unit_label' => '1 board', 'price' => 499, 'mrp' => 649, 'stock' => 18, 'sku' => 'EDU-TRACE-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],

        ['category_id' => 7, 'name' => 'Kids Hula Hoop', 'slug' => 'kids-hula-hoop', 'short_desc' => 'A light hoop for waist, ankle and floor games.', 'description' => 'Sized for preschoolers. Use it for musical statues, jumping in and out, or as a personal spot on the floor.', 'emoji' => '🟣', 'unit_label' => '1 hoop', 'price' => 249, 'mrp' => 349, 'stock' => 24, 'sku' => 'SPT-HOOP-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 7, 'name' => 'Catch and Throw Set', 'slug' => 'catch-and-throw-set', 'short_desc' => 'Two soft bats and one foam ball.', 'description' => 'A first bat-and-ball set for outdoor play. The ball is light and the bats have a short handle.', 'emoji' => '🥎', 'unit_label' => '1 set', 'price' => 449, 'mrp' => 599, 'stock' => 18, 'sku' => 'SPT-CATCH-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],
        ['category_id' => 7, 'name' => 'Play Parachute', 'slug' => 'play-parachute', 'short_desc' => 'A round sheet for group games.', 'description' => 'The class holds the edge, lifts together and watches a soft ball bounce in the middle. It folds into a small bag.', 'emoji' => '🪂', 'unit_label' => '1 sheet', 'price' => 899, 'mrp' => 1199, 'stock' => 10, 'sku' => 'SPT-CHUTE-01', 'is_bestseller' => 1, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 7, 'name' => 'Bean Bag Set', 'slug' => 'bean-bag-set', 'short_desc' => 'Six small bags for aiming and balancing.', 'description' => 'Children toss them into a basket, balance one on the head, or sort them by colour.', 'emoji' => '🟤', 'unit_label' => '6 bags', 'price' => 299, 'mrp' => 399, 'stock' => 26, 'sku' => 'SPT-BEAN-06', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],

        ['category_id' => 8, 'name' => 'Straw Lid Water Bottle', 'slug' => 'straw-lid-water-bottle', 'short_desc' => 'A small bottle with a straw lid.', 'description' => 'Opens with a flip and fits the backpack side pocket. The straw is easy for Nursery children.', 'emoji' => '🍼', 'unit_label' => '1 bottle', 'price' => 349, 'mrp' => 449, 'stock' => 30, 'sku' => 'ESS-BOTTLE-01', 'is_bestseller' => 0, 'is_trending' => 1, 'is_new' => 0],
        ['category_id' => 8, 'name' => 'Pencil Pouch', 'slug' => 'pencil-pouch', 'short_desc' => 'A zip pouch for pencils and crayons.', 'description' => 'Softer than a box, so it sits flat in the bag. One zip and a small name patch on the front.', 'emoji' => '👜', 'unit_label' => '1 pouch', 'price' => 179, 'mrp' => 249, 'stock' => 40, 'sku' => 'ESS-POUCH-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
        ['category_id' => 8, 'name' => 'ID Card with Lanyard', 'slug' => 'id-card-lanyard', 'short_desc' => 'A blank card and a breakaway lanyard.', 'description' => 'Write the child name and class, slip in a photo, and clip it on. The clasp opens if the strap is pulled.', 'emoji' => '🎫', 'unit_label' => '1 set', 'price' => 99, 'mrp' => 149, 'stock' => 80, 'sku' => 'ESS-ID-01', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 1],
        ['category_id' => 8, 'name' => 'Handkerchief Pack', 'slug' => 'handkerchief-pack', 'short_desc' => 'Six small cotton handkerchiefs.', 'description' => 'A spare set for the school bag. They are plain so a name can be written in the corner.', 'emoji' => '🤍', 'unit_label' => '6 pieces', 'price' => 149, 'mrp' => 199, 'stock' => 50, 'sku' => 'ESS-HANKY-06', 'is_bestseller' => 0, 'is_trending' => 0, 'is_new' => 0],
    ];
}
