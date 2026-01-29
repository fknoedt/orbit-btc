@php
    $bitcoinQuotes = [
        'Fix the money, fix the world',
        'Not your keys, not your coins',
        'No man should work for what another man can print',
        'Be your own bank',
        'Bitcoin fixes this',
        'When in doubt zoom out',
        'Everyone gets Bitcoin at the price they deserve',
        'Stay humble, stack sats',
        'There is no second best',
        'Trusted third parties are security holes',
    ];
    $randomQuote = $bitcoinQuotes[array_rand($bitcoinQuotes)];
@endphp

<!-- Start Footer -->
<footer class="py-8 bg-slate-800 dark:bg-gray-900">
    <div class="container">
        <div class="grid md:grid-cols-12 items-center">
            <div class="md:col-span-3">
                <a href="#" class="logo-footer">
                    <img src="assets/images/logo-light.png" class="md:ms-0 mx-auto" alt="">
                </a>
            </div>

            <div class="md:col-span-6 md:mt-0 mt-8">
                <div class="text-center">
                    <p class="text-white text-lg md:text-xl font-medium">{{ $randomQuote }} <i class="mdi mdi-bitcoin text-orange-600" style="font-size: 2rem; vertical-align: middle"></i></p>
                </div>
            </div>

            <div class="md:col-span-3">
                &nbsp;
            </div><!--end col-->
        </div><!--end row-->
    </div><!--end container-->
</footer><!--end footer-->
<!-- End Footer -->
