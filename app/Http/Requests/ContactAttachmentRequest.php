<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachments' => 'required|array|min:1',
            'attachments.*.file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt,xls,xlsx',
            'attachments.*.attachment_description' => 'nullable|string|max:500',
            'attachments.*.attachment_primary' => 'nullable|boolean',
            'attachments.*.attachment_filename' => 'nullable|string|max:255',
            'attachments.*.contact_attachment_uuid' => 'nullable|string|uuid',
            'attachments.*.attachment_uploaded_date' => 'nullable|date',
            'attachments.*.attachment_uploaded_user_uuid' => 'nullable|string|uuid',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.required' => 'At least one attachment is required.',
            'attachments.*.file.max' => 'The file must not be larger than 10MB.',
            'attachments.*.file.mimes' => 'The file must be a file of type: jpg, jpeg, png, pdf, doc, docx, txt, xls, xlsx.',
            'attachments.*.attachment_description.max' => 'The description must not exceed 500 characters.',
        ];
    }
}
