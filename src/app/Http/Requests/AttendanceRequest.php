<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'attendance_start_at' => ['required', 'date_format:H:i'],
            'attendance_finish_at' => ['required', 'date_format:H:i', 'after_or_equal:attendance_start_at'],
            'intermissions.*.start_at' => ['nullable', 'date_format:H:i', 'required_with:intermissions.*.finish_at'],
            'intermissions.*.finish_at' => ['nullable', 'date_format:H:i', 'required_with:intermissions.*.start_at'],
            'comments' => ['required'],
        ];
    }

    public function messages()
    {

        return [
            'attendance_start_at.required' => '出勤時間は必須です',
            'attendance_start_at.date_format' => '**:**の形式で時間を入力してください',

            'attendance_finish_at.required' => '退勤時間は必須です',
            'attendance_finish_at.date_format' => '**:**の形式で時間を入力してください',
            'attendance_finish_at.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',

            'intermissions.*.start_at.date_format' => '**:**の形式で時間を入力してください',
            'intermissions.*.start_at.required_with' => '休憩は開始・終了セットで入力してください',

            'intermissions.*.finish_at.date_format' => '**:**の形式で時間を入力してください',
            'intermissions.*.finish_at.required_with' => '休憩は開始・終了セットで入力してください',

            'comments.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startAt = $this->input('attendance_start_at');
            $finishAt = $this->input('attendance_finish_at');
            $rests = $this->input('intermissions', []);

            foreach ($rests as $i => $rest) {
                $restStart = $rest['start_at'] ?? null;
                $restFinish = $rest['finish_at'] ?? null;

                if ($restStart && $startAt && $restStart < $startAt) {
                    $validator->errors()->add("intermissions.$i.start_at", '休憩時間が不適切な値です');
                }

                if ($restStart && $startAt && $restStart > $finishAt) {
                    $validator->errors()->add("intermissions.$i.start_at", '休憩時間が不適切な値です');
                }

                if ($restFinish && $finishAt && $restFinish > $finishAt) {
                    $validator->errors()->add("intermissions.$i.finish_at", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($restStart && $restFinish && $restFinish < $restStart) {
                    $validator->errors()->add("intermissions.$i.finish_at", '休憩時間が開始・終了が逆転しています');
                }
            }
        });
    }
}
