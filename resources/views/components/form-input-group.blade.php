<!-- Form Input Group Component -->
<!-- Gunakan seperti: @include('components.form-input-group', ['label' => 'Nama', 'name' => 'form.name', 'rules' => 'required']) -->
<div>
    <label class="text-xs font-semibold text-gray-500">[[ $label ]]</label>
    <input type="[[ $type ?? 'text' ]]" name="[[ $name ]]" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600" [[ $required ? 'required' : '' ]]>
</div>