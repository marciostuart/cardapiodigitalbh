<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#EA4335] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#D9362A] focus:bg-[#D9362A] active:bg-[#B92C22] focus:outline-none focus:ring-2 focus:ring-[#EA4335] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
