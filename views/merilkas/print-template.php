<?php
/**
 * Шаблон Обмірного свідоцтва для виводу на друк (А4).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\UserFstu\Merilkas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $item Дані судна та мерилки, передані з контролера */
// Функція для безпечного виводу нулів і порожніх значень
function fstu_print_val( $val ) {
	return '' !== trim( (string) $val ) ? esc_html( $val ) : '—';
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
	<meta charset="UTF-8">
	<title>Обмірне свідоцтво - <?php echo esc_html( $item['Sailboat_Name'] ); ?></title>
	<style>
		/* Базові стилі тільки для цього документа */
		body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #000; background: #fff; margin: 0; padding: 20px; line-height: 1.3; }
		.print-header { text-align: center; margin-bottom: 20px; }
		.print-header h2 { margin: 0 0 5px; font-size: 18px; text-transform: uppercase; }
		.print-header h1 { margin: 0 0 10px; font-size: 22px; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 10px; }
		.print-info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
		
		/* Таблиці */
		.print-section { margin-bottom: 15px; }
		.print-section-title { font-weight: bold; background: #f0f0f0; padding: 4px; border: 1px solid #000; text-align: center; text-transform: uppercase; margin: 0; }
		.print-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
		.print-table td, .print-table th { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
		.print-table .label { width: 60%; }
		.print-table .val { width: 40%; text-align: center; font-weight: bold; }
		
		/* Сітка на 3 колонки для параметрів */
		.print-columns { display: flex; gap: 15px; }
		.print-col { flex: 1; }

		.print-footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 14px; }
		.print-sign { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }

		/* Налаштування сторінки для принтера */
		@page { size: A4; margin: 15mm; }
		@media print {
			body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.no-print { display: none !important; }
		}
	</style>
</head>
<body>

	<div class="print-header">
		<h2>Федерація Спортивного Туризму України</h2>
		<h1>Обмірне свідоцтво вітрильного судна</h1>
	</div>

	<div class="print-info-grid">
		<div>
			<div><strong>Назва судна:</strong> <?php echo fstu_print_val( $item['Sailboat_Name'] ); ?></div>
			<div><strong>№ на вітрилі:</strong> <?php echo fstu_print_val( $item['Sailboat_NumberSail'] ); ?></div>
			<div><strong>Рік побудови:</strong> <?php echo fstu_print_val( $item['Sailboat_Year'] ); ?></div>
		</div>
		<div style="text-align: right;">
			<div><strong>ПІБ Власника:</strong> <?php echo fstu_print_val( $item['FIO'] ); ?></div>
			<div><strong>Дата обміру:</strong> <?php echo fstu_print_val( date('d.m.Y', strtotime($item['MR_DateObmera'])) ); ?></div>
		</div>
	</div>

	<div class="print-columns">
		<div class="print-col">
			<div class="print-section">
				<h3 class="print-section-title">Екіпаж та Корпус</h3>
				<table class="print-table">
					<tr><td class="label">Кіл-ть екіпажу</td><td class="val"><?php echo fstu_print_val($item['MR_GrevNumber']); ?></td></tr>
					<tr><td class="label">Вага екіпажу (кг)</td><td class="val"><?php echo fstu_print_val($item['MR_CrewWeight']); ?></td></tr>
					<tr><td class="label">Вага корпусу (кг)</td><td class="val"><?php echo fstu_print_val($item['MR_Weight']); ?></td></tr>
					<tr><td class="label">Довжина AL (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Length']); ?></td></tr>
				</table>
			</div>
			<div class="print-section">
				<h3 class="print-section-title">Щогла</h3>
				<table class="print-table">
					<tr><td class="label">Попер. D (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Machta_PPD']); ?></td></tr>
					<tr><td class="label">Позд. D1 (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Machta_PRD']); ?></td></tr>
					<tr><td class="label">Ліктрос d (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Liktros']); ?></td></tr>
				</table>
			</div>
		</div>

		<div class="print-col">
			<div class="print-section">
				<h3 class="print-section-title">Грот</h3>
				<table class="print-table">
					<tr><td class="label">P (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Grot_P']); ?></td></tr>
					<tr><td class="label">B (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Grot_B']); ?></td></tr>
					<tr><td class="label">E (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Grot_E']); ?></td></tr>
					<tr><td class="label">VLM (м)</td><td class="val"><?php echo fstu_print_val($item['MR_Grot_VLM']); ?></td></tr>
					<tr><td class="label" style="background:#e8f4f8;">Площа (кв.м)</td><td class="val" style="background:#e8f4f8;"><?php echo fstu_print_val($item['MR_Area_Grot']); ?></td></tr>
				</table>
			</div>
		</div>

		<div class="print-col">
			<div class="print-section">
				<h3 class="print-section-title">Стаксель / Спінакер</h3>
				<table class="print-table">
					<tr><td class="label">Стаксель P</td><td class="val"><?php echo fstu_print_val($item['MR_Staksel_P']); ?></td></tr>
					<tr><td class="label">Стаксель E</td><td class="val"><?php echo fstu_print_val($item['MR_Staksel_E']); ?></td></tr>
					<tr><td class="label" style="background:#e8f4f8;">Площа (кв.м)</td><td class="val" style="background:#e8f4f8;"><?php echo fstu_print_val($item['MR_Area_Staksel']); ?></td></tr>
					<tr><td colspan="2" style="border:none; height:10px;"></td></tr>
					<tr><td class="label">Спінакер SMW</td><td class="val"><?php echo fstu_print_val($item['MR_Spinaker_SMW']); ?></td></tr>
					<tr><td class="label" style="background:#e8f4f8;">Площа (кв.м)</td><td class="val" style="background:#e8f4f8;"><?php echo fstu_print_val($item['MR_Area_Spinaker']); ?></td></tr>
				</table>
			</div>
		</div>
	</div>

	<div class="print-section" style="margin-top: 20px;">
		<h3 class="print-section-title" style="background: #fff3cd;">РОЗРАХУНОК ГОНОЧНОГО БАЛА (ГБ)</h3>
		<table class="print-table">
			<tr style="background: #fdf6e3;">
				<th>Параметр</th>
				<th>БЕЗ урахування ваги екіпажу</th>
				<th>З урахуванням ваги екіпажу</th>
			</tr>
			<tr>
				<td class="label"><strong>ГБ БЕЗ спінакера</strong></td>
				<td class="val"><?php echo fstu_print_val($item['MR_GB']); ?></td>
				<td class="val"><?php echo fstu_print_val($item['MR_GB_CrewWeight']); ?></td>
			</tr>
			<tr>
				<td class="label"><strong>ГБ ЗІ спінакером</strong></td>
				<td class="val"><?php echo fstu_print_val($item['MR_GB_Spinaker']); ?></td>
				<td class="val"><?php echo fstu_print_val($item['MR_GB_CrewWeight_Spinaker']); ?></td>
			</tr>
		</table>
	</div>

	<div class="print-footer">
		<div>
			М.П.<br><br><br>
			<div class="print-sign">Голова Вітрильної Комісії</div>
		</div>
		<div>
			<br><br><br>
			<div class="print-sign">Власник судна</div>
		</div>
	</div>

	<div class="no-print" style="text-align: center; margin-top: 30px;">
		<button onclick="window.print();" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #c0392b; color: #fff; border: none; border-radius: 4px;">🖨 РОЗДРУКУВАТИ / ЗБЕРЕГТИ В PDF</button>
		<button onclick="window.close();" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #f0f0f0; color: #333; border: 1px solid #ccc; border-radius: 4px; margin-left: 10px;">Закрити вікно</button>
	</div>

	<script>
		// Автоматично викликаємо вікно друку після завантаження сторінки
		window.onload = function() {
			window.print();
		};
	</script>
</body>
</html>