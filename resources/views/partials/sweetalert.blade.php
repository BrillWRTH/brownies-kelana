{{-- SweetAlert2 Notifikasi Pop-up Pojok Kanan Atas --}}
@php
    $swalType = null;
    $swalTitle = null;
    $swalHtml = null;
    $swalTimer = 4000;

    if (session('success')) {
        $swalType = 'success';
        $swalTitle = session('success');
        if (session('wa_launch_url')) {
            $swalHtml = '<div class="mt-2"><a href="' . e(session('wa_launch_url')) . '" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition"><span>Buka Chat WhatsApp Sekarang</span> &rarr;</a></div>';
            $swalTimer = 9000;
        }
    } elseif (session('error')) {
        $swalType = 'error';
        $swalTitle = session('error');
        $swalTimer = 5000;
    } elseif (session('warning')) {
        $swalType = 'warning';
        $swalTitle = session('warning');
        $swalTimer = 5000;
    } elseif (session('info')) {
        $swalType = 'info';
        $swalTitle = session('info');
    } elseif (session('status')) {
        $swalType = 'info';
        $swalTitle = session('status');
    } elseif ($errors->any()) {
        $swalType = 'error';
        $swalTitle = 'Periksa kembali input Anda:';
        $errList = '<ul style="text-align:left;margin-top:6px;padding-left:18px;list-style-type:disc;font-size:12px;line-height:1.45;">';
        foreach ($errors->all() as $err) {
            $errList .= '<li>' . e($err) . '</li>';
        }
        $errList .= '</ul>';
        $swalHtml = $errList;
        $swalTimer = 6500;
    }
@endphp

@if($swalType)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function triggerKelanaToast() {
                if (typeof window.Toast !== 'undefined') {
                    window.Toast.fire({
                        icon: @json($swalType),
                        title: @json($swalTitle),
                        @if($swalHtml)
                            html: {!! json_encode($swalHtml) !!},
                        @endif
                        timer: {{ $swalTimer }}
                    });
                } else {
                    setTimeout(triggerKelanaToast, 50);
                }
            }
            triggerKelanaToast();
        });
    </script>
@endif
