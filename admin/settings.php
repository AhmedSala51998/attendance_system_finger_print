<?php
include "../config.php";
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login");
    exit();
}

$msg = "";
if (isset($_POST['update'])) {
    $lat = $_POST['company_lat'];
    $lng = $_POST['company_lng'];
    $dist = $_POST['allowed_distance'];
    $u = $conn->query("UPDATE settings SET company_lat='$lat', company_lng='$lng', allowed_distance='$dist' WHERE id=1");
    if ($u)
        $msg = "<script>Swal.fire('تم!', 'تم حفظ الإعدادات بنجاح', 'success');</script>";
}

$set = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();
if (!$set) {
    $set = ['company_lat' => '24.7136', 'company_lng' => '46.6753', 'allowed_distance' => '200'];
}
include "../layout/header.php";
?>

<div class="dashboard" style="margin: 40px auto; max-width: 900px;">
    <?php echo $msg; ?>

    <h2 style="margin-bottom: 25px;"><i class="fas fa-map-marked-alt" style="color:var(--primary)"></i> إعدادات الموقع
        الجغرافي والشركة</h2>

    <form method="POST"
        style="background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); padding:30px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">

        <div style="position:relative; margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-compass" id="search-icon"
                        style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--primary); z-index:1000; transition: all 0.3s;"></i>
                    <i class="fas fa-circle-notch fa-spin" id="search-loader"
                        style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--primary); z-index:1000; display:none;"></i>

                    <input type="text" id="map-search" placeholder="اكتب اسم الموقع هنا (مثال: ينبع شركة إيجاز)..."
                        autocomplete="off"
                        style="width:100%; padding:15px 45px 15px 15px; border:2px solid #E5E7EB; border-radius:12px; font-size:15px; background:white; outline:none; direction:rtl; transition: border-color 0.4s ease;"
                        onkeypress="if(event.keyCode==13){event.preventDefault();}">
                    <div id="search-results"
                        style="position:absolute; width:100%; top:55px; background:white; border-radius:12px; box-shadow:0 15px 30px rgba(0,0,0,0.15); z-index:9999; display:none; max-height:200px; overflow-y:auto; border: 1px solid #eee;">
                    </div>
                </div>
                <button type="button" onclick="getLocation()"
                    style="width: auto; padding: 0 20px; background: #fff; color: var(--primary); border: 2px solid #E5E7EB; border-radius: 12px; font-weight: bold; cursor: pointer;">
                    <i class="fas fa-crosshairs"></i> موقعي
                </button>
            </div>
            <p style="color: #6b7280; font-size: 13px; margin: 10px 5px 0 0;"><i class="fas fa-info-circle"></i> يمكنك
                كتابة اسم المكان، أو <b>لصق رابط جوجل مابس (Google Maps)</b> مباشرة هنا للحصول على الموقع بدقة.</p>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <div id="map"
            style="height: 450px; border-radius: 15px; margin-bottom: 20px; border:2px solid #E5E7EB; z-index:1;"></div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
            <div class="input-group">
                <label>خط العرض (Lat)</label>
                <div style="position:relative;">
                    <i class="fas fa-map-marker-alt"
                        style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--primary);"></i>
                    <input type="text" name="company_lat" id="lat-input" value="<?php echo $set['company_lat']; ?>"
                        readonly style="background:#F9FAFB; padding-right: 45px; width: 100%;">
                </div>
            </div>
            <div class="input-group">
                <label>خط الطول (Lng)</label>
                <div style="position:relative;">
                    <i class="fas fa-map-marker-alt"
                        style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--primary);"></i>
                    <input type="text" name="company_lng" id="lng-input" value="<?php echo $set['company_lng']; ?>"
                        readonly style="background:#F9FAFB; padding-right: 45px; width: 100%;">
                </div>
            </div>
        </div>

        <div class="input-group">
            <label>المسافة المسموحة (بالمتر)</label>
            <div style="position:relative;">
                <i class="fas fa-ruler-combined"
                    style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--primary);"></i>
                <input type="number" name="allowed_distance" value="<?php echo $set['allowed_distance']; ?>" required
                    style="padding-right: 45px; width: 100%;">
            </div>
        </div>

        <button type="submit" name="update" class="btn">
            <i class="fas fa-check-circle" style="margin-left: 8px;"></i> اعتمد الإعدادات الجديدة
        </button>
    </form>

    <div style="margin-top:20px; text-align:center;">
        <a href="index" style="color:var(--primary); text-decoration:none; font-weight:600;">
            <i class="fas fa-arrow-right"></i> عودة للوحة تحكم الإدارة
        </a>
    </div>
</div>

<script>
    // تهيئة الخريطة
    var map = L.map('map').setView([<?php echo $set['company_lat']; ?>, <?php echo $set['company_lng']; ?>], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

    // تهيئة الدبوس مع رسالة منبثقة
    var marker = L.marker([<?php echo $set['company_lat']; ?>, <?php echo $set['company_lng']; ?>], { draggable: true }).addTo(map);
    marker.bindPopup("موقع الشركة الحالي").openPopup();

    // عند سحب الدبوس يدوياً
    marker.on('dragend', function (e) {
        var pos = e.target.getLatLng();
        updateCoords(pos);
        getNominatimName(pos.lat, pos.lng); // جلب الاسم آلياً عند السحب
    });

    function updateCoords(pos) {
        document.getElementById('lat-input').value = parseFloat(pos.lat).toFixed(6);
        document.getElementById('lng-input').value = parseFloat(pos.lng).toFixed(6);
    }

    // دالة ذكية لجلب اسم المكان من الإحداثيات (Reverse Geocoding)
    function getNominatimName(lat, lon, extraName = "") {
        marker.setPopupContent("جاري التعرف على المكان...").openPopup();
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=ar`)
            .then(r => r.json())
            .then(data => {
                let address = data.display_name || "موقع غير معروف";

                // إذا وجدنا اسماً خاصاً بالمصلحة/الشركة ندمجه
                let finalName = extraName ? `${address} (${extraName})` : address;

                marker.bindPopup(`<div style="direction:rtl; text-align:right; font-family:inherit;"><b>${finalName}</b></div>`).openPopup();
                searchInput.value = finalName;
            })
            .catch(() => {
                marker.setPopupContent("موقع مخصص (تعذر جلب الاسم)").openPopup();
            });
    }

    function getLocation() {
        if (navigator.geolocation) {
            Swal.fire({ title: 'جاري تحديد موقعك...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            navigator.geolocation.getCurrentPosition(function (pos) {
                Swal.close();
                var lat = pos.coords.latitude;
                var lon = pos.coords.longitude;
                moveTo(lat, lon, 17, "موقعي الحالي");
                getNominatimName(lat, lon);
            });
        }
    }

    function moveTo(lat, lon, zoom, name = "الموقع المختار") {
        map.setView([lat, lon], zoom);
        marker.setLatLng([lat, lon]);
        marker.bindPopup(`<div style="direction:rtl; text-align:right; font-family:inherit;"><b>${name}</b></div>`).openPopup();
        updateCoords({ lat: lat, lng: lon });
    }

    var searchTimer;
    const searchInput = document.getElementById('map-search');
    const resultsBox = document.getElementById('search-results');
    const searchIcon = document.getElementById('search-icon');
    const searchLoader = document.getElementById('search-loader');

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const query = this.value.trim();

        // إعادة ضبط الحالة عند المسح
        if (query.length === 0) {
            searchInput.style.borderColor = "#E5E7EB";
            return;
        }

        // إظهار مؤشر التحميل
        searchIcon.style.opacity = "0";
        searchLoader.style.display = "block";

        // دعم روابط جوجل مابس وفك اسم المكان
        if (query.includes('google.com/maps') || query.includes('maps.app.goo.gl') || query.includes('@')) {
            let lat = null, lng = null, placeName = "";
            const nameRegex = /\/place\/([^\/]+)\//;
            const nameMatch = query.match(nameRegex);
            if (nameMatch) {
                try { placeName = decodeURIComponent(nameMatch[1].replace(/\+/g, ' ')); } catch (e) { }
            }
            const placeRegex = /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/;
            const placeMatch = query.match(placeRegex);
            if (placeMatch) {
                lat = parseFloat(placeMatch[1]);
                lng = parseFloat(placeMatch[2]);
            } else {
                const centerRegex = /@(-?\d+\.\d+),(-?\d+\.\d+)/;
                const centerMatch = query.match(centerRegex);
                if (centerMatch) {
                    lat = parseFloat(centerMatch[1]);
                    lng = parseFloat(centerMatch[2]);
                }
            }

            if (lat && lng) {
                moveTo(lat, lng, 17, "جاري استخراج البيانات...");
                getNominatimName(lat, lng, placeName);
                searchIcon.style.opacity = "1";
                searchLoader.style.display = "none";
                searchInput.style.borderColor = "var(--primary)";
                Swal.fire({ icon: 'success', title: 'تم التعرف على الرابط!', text: placeName ? `أهلاً بك في ${placeName}` : 'تم استخراج الإحداثيات بنجاح.', timer: 2000, showConfirmButton: false });
                resultsBox.style.display = 'none';
                return;
            }
        }

        if (query.length < 3) {
            searchIcon.style.opacity = "1";
            searchLoader.style.display = "none";
            resultsBox.style.display = 'none';
            return;
        }

        searchTimer = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=sa&accept-language=ar`)
                .then(r => r.json())
                .then(data => {
                    searchIcon.style.opacity = "1";
                    searchLoader.style.display = "none";

                    resultsBox.innerHTML = '';
                    if (data.length > 0) {
                        searchInput.style.borderColor = "var(--primary)"; // نجاح
                        var first = data[0];
                        moveTo(parseFloat(first.lat), parseFloat(first.lon), 17, first.display_name);

                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.style.padding = '12px 15px';
                            div.style.cursor = 'pointer';
                            div.style.borderBottom = '1px solid #f1f5f9';
                            div.innerHTML = `<i class="fas fa-map-marker-alt" style="color:var(--primary); margin-left:10px;"></i> ${item.display_name}`;
                            div.onclick = function () {
                                moveTo(parseFloat(item.lat), parseFloat(item.lon), 17, item.display_name);
                                resultsBox.style.display = 'none';
                                searchInput.value = item.display_name;
                            };
                            resultsBox.appendChild(div);
                        });
                        resultsBox.style.display = 'block';
                    } else {
                        searchInput.style.borderColor = "#ef4444"; // فشل
                    }
                })
                .catch(() => {
                    searchIcon.style.opacity = "1";
                    searchLoader.style.display = "none";
                    searchInput.style.borderColor = "#ef4444";
                });
        }, 1000);
    });

    document.addEventListener('click', function (e) { if (e.target !== searchInput) resultsBox.style.display = 'none'; });
</script>

<?php include "../layout/footer.php"; ?>