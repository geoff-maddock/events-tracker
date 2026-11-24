{{--
    Where toast messages appear (#2397): two live regions that are on the page
    from the start, so a message added later is announced. window.notify() in
    resources/assets/js/toast.js fills them; errors and warnings go in the
    alert region and stay until closed.
--}}
<div class="fixed top-4 inset-x-4 sm:left-auto sm:right-4 sm:w-96 z-[2500] flex flex-col gap-2 pointer-events-none">
	<div id="toast-alerts" role="alert" class="flex flex-col gap-2"></div>
	<div id="toast-status" role="status" class="flex flex-col gap-2"></div>
</div>
