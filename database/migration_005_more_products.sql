-- Extra preschool products for every category.
-- Run once on u750208840_storepredb. Safe if a slug already exists.
-- Do not import storepreschool.sql again. It drops the tables.
-- The website also adds these products, vendor links and dark-store stock on the next page load.

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Animal Finger Puppet Set', 'animal-finger-puppets', 'Five soft puppets for circle-time stories.', 'A dog, cat, bird, elephant and lion that fit a small finger. Teachers use them for stories, animal sounds and turn-taking.', '🖐️', '5 puppets', 249, 349, 40, 'TOY-PUPPET-05', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'toys'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'animal-finger-puppets');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Wooden Pull-Along Duck', 'wooden-pull-along-duck', 'A duck on wheels with a short pull string.', 'Children pull it across the classroom floor. The wheels are quiet and the string is short so it stays in the play area.', '🦆', '1 toy', 399, 549, 24, 'TOY-DUCK-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'toys'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'wooden-pull-along-duck');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Stacking Ring Pyramid', 'stacking-ring-pyramid', 'Eight rings that stack from big to small.', 'A first stacking toy for Nursery. The centre post keeps the rings in place and the colours are easy to name.', '🔺', '8 rings', 299, 399, 36, 'TOY-RINGS-08', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'toys'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'stacking-ring-pyramid');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Soft Activity Cube', 'soft-activity-cube', 'A fabric cube with crinkle, mirror and tags.', 'Each side has a different touch: a mirror, a crinkle patch, a ribbon and a squeaker. It is light enough for the youngest class.', '🎲', '1 cube', 549, 749, 20, 'TOY-ACTCUBE-01', 1, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'toys'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'soft-activity-cube');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Colours and Shapes Picture Book', 'colours-shapes-picture-book', 'One colour or shape on each thick page.', 'A board book for naming red, blue, circle and square. The pictures are large enough for a small group to see.', '🌈', '1 book', 179, 249, 40, 'BOK-COLOR-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'books'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'colours-shapes-picture-book');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Good Habits Board Book', 'good-habits-board-book', 'Wash hands, share toys and tidy up.', 'Four short pages on everyday class habits. Teachers read one page at the start of the day.', '🧼', '1 book', 199, 279, 35, 'BOK-HABIT-01', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'books'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'good-habits-board-book');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Hindi Varnamala Picture Book', 'hindi-varnamala-picture-book', 'Each Hindi letter with a familiar picture.', 'A first Hindi alphabet book for circle time. Every letter sits next to an object children already know.', '🔤', '1 book', 219, 299, 30, 'BOK-HINDI-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'books'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'hindi-varnamala-picture-book');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Bedtime Animal Stories', 'bedtime-animal-stories', 'Five very short animal stories.', 'Each story ends in a few minutes, so it fits rest time or a send-home reading bag.', '🌙', '1 book', 249, 349, 28, 'BOK-SLEEP-01', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'books'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'bedtime-animal-stories');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Eraser and Sharpener Pack', 'eraser-sharpener-pack', 'Six soft erasers and six safe sharpeners.', 'A class refill pack. The sharpeners have a small bin so shavings stay off the table.', '🧽', '6 sets', 79, 119, 80, 'STA-ERASE-06', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'stationery'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'eraser-sharpener-pack');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Washable Sketch Pens', 'washable-sketch-pens', '12 bright pens that wash off skin.', 'Felt tips for drawing and labelling charts. Marks on hands wash away with soap and water.', '🖊️', '12 colours', 129, 179, 60, 'STA-SKETCH-12', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'stationery'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'washable-sketch-pens');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Two-Compartment Pencil Box', 'two-compartment-pencil-box', 'A box that keeps pencils and crayons apart.', 'The lid clicks shut and the tray lifts out. It fits the preschool backpack pocket.', '📦', '1 box', 199, 279, 40, 'STA-BOX-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'stationery'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'two-compartment-pencil-box');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'A4 Drawing Book Pack', 'a4-drawing-book-pack', 'Three plain books for drawing class.', 'Thick blank pages that take crayon and sketch pen without tearing. A simple pack for a term of art.', '🖼️', '3 books', 149, 199, 50, 'STA-DRAW-03', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'stationery'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'a4-drawing-book-pack');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Watercolour Cake Set', 'watercolour-cake-set', '12 dry colour cakes and one brush.', 'Children dip the brush and paint. The cakes stay in the tin, so the set is easier to store than bottles.', '🎨', '12 colours', 249, 349, 30, 'ART-WATER-12', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'art-craft'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'watercolour-cake-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Pom Pom and Googly Eye Pack', 'pom-pom-googly-eye-pack', 'Pom poms, eyes and glue dots for collage.', 'A craft-hour pack for faces and animals. The pieces are large enough for little fingers to pick up.', '👀', '1 pack', 129, 179, 45, 'ART-POM-01', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'art-craft'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'pom-pom-googly-eye-pack');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Child Art Apron 2 Pack', 'child-art-apron-2-pack', 'Two wipe-clean aprons with a front pocket.', 'Sleeveless aprons that go on over the uniform. Paint wipes off and the pocket holds a brush.', '👕', '2 aprons', 299, 399, 25, 'ART-APRON-02', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'art-craft'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'child-art-apron-2-pack');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Sponge Stamp Shape Set', 'sponge-stamp-shape-set', 'Six shape stamps with easy handles.', 'Circles, stars and triangles children press onto paper. The handles keep paint off their palms.', '🔶', '6 stamps', 199, 279, 32, 'ART-STAMP-06', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'art-craft'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'sponge-stamp-shape-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Classroom Storage Bin Set', 'classroom-storage-bin-set', 'Six open bins for blocks, books and dolls.', 'Low bins children can carry to the shelf. Labels fit on the front so the class can tidy by picture.', '🧺', '6 bins', 1599, 1999, 10, 'FUR-BIN-06', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'furniture'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'classroom-storage-bin-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Teacher Story-Time Chair', 'teacher-story-time-chair', 'A low chair for reading to a circle.', 'Sits at child height so the book faces the group. The frame is light enough to move to the mat.', '🪑', '1 chair', 1899, 2499, 8, 'FUR-TCHAIR-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'furniture'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'teacher-story-time-chair');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Eight-Cubbie Bag Rack', 'eight-cubbie-bag-rack', 'Eight open cubbies for bags and bottles.', 'A wall-side rack for a small class. Each cubbie holds one backpack and has room for a name card.', '🗄️', '8 cubbies', 4999, 6299, 6, 'FUR-CUBBY-08', 1, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'furniture'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'eight-cubbie-bag-rack');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Reading Corner Cushion Pair', 'reading-corner-cushion-pair', 'Two washable floor cushions.', 'Soft seats for the book corner. Covers come off for washing after a term of sitting and snacking.', '🛋️', '2 cushions', 999, 1299, 14, 'FUR-CUSH-02', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'furniture'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'reading-corner-cushion-pair');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Number Cards 1 to 20', 'number-cards-1-to-20', 'One card for each number, with dots to count.', 'Children match the numeral to the dots. The cards are rounded and large enough for the mat.', '🔢', '20 cards', 199, 279, 40, 'EDU-NUM-20', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'educational-materials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'number-cards-1-to-20');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Lacing Beads Set', 'lacing-beads-set', 'Chunky beads and two laces.', 'A fine-motor shelf activity. Beads are too big to swallow and the laces have a stiff tip.', '🧵', '1 set', 349, 449, 28, 'EDU-LACE-01', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'educational-materials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'lacing-beads-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Weather and Seasons Chart', 'weather-seasons-chart', 'A wall chart with movable weather cards.', 'The class pins today weather each morning: sun, cloud, rain or wind, plus the four seasons.', '⛅', '1 chart', 249, 329, 22, 'EDU-WEATH-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'educational-materials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'weather-seasons-chart');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Letter Tracing Board', 'letter-tracing-board', 'Grooved letters for finger tracing.', 'Children trace uppercase letters with a finger before they write with a pencil. The board wipes clean.', '✍️', '1 board', 499, 649, 18, 'EDU-TRACE-01', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'educational-materials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'letter-tracing-board');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Kids Hula Hoop', 'kids-hula-hoop', 'A light hoop for waist, ankle and floor games.', 'Sized for preschoolers. Use it for musical statues, jumping in and out, or as a personal spot on the floor.', '🟣', '1 hoop', 249, 349, 24, 'SPT-HOOP-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'sports'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'kids-hula-hoop');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Catch and Throw Set', 'catch-and-throw-set', 'Two soft bats and one foam ball.', 'A first bat-and-ball set for outdoor play. The ball is light and the bats have a short handle.', '🥎', '1 set', 449, 599, 18, 'SPT-CATCH-01', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'sports'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'catch-and-throw-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Play Parachute', 'play-parachute', 'A round sheet for group games.', 'The class holds the edge, lifts together and watches a soft ball bounce in the middle. It folds into a small bag.', '🪂', '1 sheet', 899, 1199, 10, 'SPT-CHUTE-01', 1, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'sports'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'play-parachute');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Bean Bag Set', 'bean-bag-set', 'Six small bags for aiming and balancing.', 'Children toss them into a basket, balance one on the head, or sort them by colour.', '🟤', '6 bags', 299, 399, 26, 'SPT-BEAN-06', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'sports'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'bean-bag-set');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Straw Lid Water Bottle', 'straw-lid-water-bottle', 'A small bottle with a straw lid.', 'Opens with a flip and fits the backpack side pocket. The straw is easy for Nursery children.', '🍼', '1 bottle', 349, 449, 30, 'ESS-BOTTLE-01', 0, 1, 0, 'none'
FROM categories c
WHERE c.slug = 'school-essentials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'straw-lid-water-bottle');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Pencil Pouch', 'pencil-pouch', 'A zip pouch for pencils and crayons.', 'Softer than a box, so it sits flat in the bag. One zip and a small name patch on the front.', '👜', '1 pouch', 179, 249, 40, 'ESS-POUCH-01', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'school-essentials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'pencil-pouch');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'ID Card with Lanyard', 'id-card-lanyard', 'A blank card and a breakaway lanyard.', 'Write the child name and class, slip in a photo, and clip it on. The clasp opens if the strap is pulled.', '🎫', '1 set', 99, 149, 80, 'ESS-ID-01', 0, 0, 1, 'none'
FROM categories c
WHERE c.slug = 'school-essentials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'id-card-lanyard');

INSERT INTO products (category_id, name, slug, short_desc, description, emoji, unit_label, price, mrp, stock, sku, is_bestseller, is_trending, is_new, kit_type)
SELECT c.id, 'Handkerchief Pack', 'handkerchief-pack', 'Six small cotton handkerchiefs.', 'A spare set for the school bag. They are plain so a name can be written in the corner.', '🤍', '6 pieces', 149, 199, 50, 'ESS-HANKY-06', 0, 0, 0, 'none'
FROM categories c
WHERE c.slug = 'school-essentials'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = 'handkerchief-pack');

