</main>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Jalaali Moment for Persian Calendar -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-jalaali/0.9.2/moment-jalaali.js"></script>

    <!-- Persian Datepicker -->
    <script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Custom Scripts -->
    <script src="/dabestan/assets/js/simple-lightbox.min.js"></script>
    <script src="/dabestan/assets/js/script.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Persian Datepicker
            $(".persian-datepicker").persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                observer: true,
                initialValue: false
            });

            // Initialize Lightbox
            var lightbox = new SimpleLightbox('.rental-items-grid a', { /* options */ });
        });
    </script>
</body>
</html>
