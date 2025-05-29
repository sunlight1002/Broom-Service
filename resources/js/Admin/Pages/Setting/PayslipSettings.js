import React, { useState, useEffect } from 'react';
import Sidebar from "../../Layouts/Sidebar";
import { useTranslation } from "react-i18next";
import axios from 'axios';
import { useAlert } from "react-alert";

export default function PayslipSettings() {
    const { t } = useTranslation();
    const alert = useAlert();
    const [errors, setErrors] = useState({});

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [settings, setSettings] = useState({
        overtimeRate125: '',
        overtimeRate150: '',
        holidayPay175: '',
        holidayPay200: '',
        bonusAfterOneYear: '',
        bonusAfterSixYears: '',
        publicHolidayBonus: '',
        workerDeduction: '',
        recoveryFee: '',
        drivingFeeDay: '',
        drivingFeeMonth: '',
    });

    const [isEditMode, setIsEditMode] = useState(false);

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const { data } = await axios.get('/api/admin/settings/get', { headers });
                setSettings(data);
            } catch (error) {
                console.error('Error fetching settings:', error);
            }
        };
        fetchSettings();
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSettings(prevSettings => ({
            ...prevSettings,
            [name]: value,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});

        const newErrors = {};
        for (const [key, value] of Object.entries(settings)) {
            if (!value) {
                const fieldName = key.replace(/([A-Z])/g, ' $1');
                newErrors[key] = [`${t(`The ${fieldName}`)} is required.`];
            }
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        try {
            const response = await axios.post('/api/admin/settings/save', settings, { headers });
            alert.success(isEditMode ? t("Data updated successfully") : t("Data saved successfully"));
            setErrors({});
        } catch (error) {
            console.error('Error saving settings:', error);
            if (error.response && error.response.status === 422) {
                setErrors(error.response.data.errors);
            } else {
                alert.error(t("errorSavingSettings"));
            }
        }
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t('admin.sidebar.settings.payslip_settings')}</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                    <form onSubmit={handleSubmit} className="row g-3" noValidate>
                        {/* Column 1 */}
                        <div className="col-md-6">
                            <div className="form-group">
                                <label htmlFor="overtimeRate125">{t("admin.payslip.overtime_rate9to10")}:</label>
                                <input
                                    type="number"
                                    name="overtimeRate125"
                                    value={settings.overtimeRate125}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.overtimeRate125 ? 'is-invalid' : ''}`}
                                />
                                {errors.overtimeRate125 && <div className="invalid-feedback">{errors.overtimeRate125[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="overtimeRate150">{t("admin.payslip.overtime_rate11to12")}:</label>
                                <input
                                    type="number"
                                    name="overtimeRate150"
                                    value={settings.overtimeRate150}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.overtimeRate150 ? 'is-invalid' : ''}`}
                                />
                                {errors.overtimeRate150 && <div className="invalid-feedback">{errors.overtimeRate150[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="holidayPay175">{t("admin.payslip.holiday_pay2")}:</label>
                                <input
                                    type="number"
                                    name="holidayPay175"
                                    value={settings.holidayPay175}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.holidayPay175 ? 'is-invalid' : ''}`}
                                />
                                {errors.holidayPay175 && <div className="invalid-feedback">{errors.holidayPay175[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="bonusAfterOneYear">{t("admin.payslip.bonus_after1")}:</label>
                                <input
                                    type="number"
                                    name="bonusAfterOneYear"
                                    value={settings.bonusAfterOneYear}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.bonusAfterOneYear ? 'is-invalid' : ''}`}
                                />
                                {errors.bonusAfterOneYear && <div className="invalid-feedback">{errors.bonusAfterOneYear[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="publicHolidayBonus">{t("admin.payslip.public_holiday_bonus")}:</label>
                                <input
                                    type="number"
                                    name="publicHolidayBonus"
                                    value={settings.publicHolidayBonus}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.publicHolidayBonus ? 'is-invalid' : ''}`}
                                />
                                {errors.publicHolidayBonus && <div className="invalid-feedback">{errors.publicHolidayBonus[0]}</div>}
                            </div>
                        </div>

                        {/* Column 2 */}
                        <div className="col-md-6">
                            <div className="form-group">
                                <label htmlFor="holidayPay200">{t("admin.payslip.holiday_pay3")}:</label>
                                <input
                                    type="number"
                                    name="holidayPay200"
                                    value={settings.holidayPay200}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.holidayPay200 ? 'is-invalid' : ''}`}
                                />
                                {errors.holidayPay200 && <div className="invalid-feedback">{errors.holidayPay200[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="bonusAfterSixYears">{t("admin.payslip.bonus_after6")}:</label>
                                <input
                                    type="number"
                                    name="bonusAfterSixYears"
                                    value={settings.bonusAfterSixYears}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.bonusAfterSixYears ? 'is-invalid' : ''}`}
                                />
                                {errors.bonusAfterSixYears && <div className="invalid-feedback">{errors.bonusAfterSixYears[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="workerDeduction">{t("admin.payslip.worker_deduction")}:</label>
                                <input
                                    type="number"
                                    name="workerDeduction"
                                    value={settings.workerDeduction}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.workerDeduction ? 'is-invalid' : ''}`}
                                />
                                {errors.workerDeduction && <div className="invalid-feedback">{errors.workerDeduction[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="recoveryFee">{t("admin.payslip.recorvery_fee")}:</label>
                                <input
                                    type="number"
                                    name="recoveryFee"
                                    value={settings.recoveryFee}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.recoveryFee ? 'is-invalid' : ''}`}
                                />
                                {errors.recoveryFee && <div className="invalid-feedback">{errors.recoveryFee[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="drivingFeeDay">{t("admin.payslip.driving_fee_perDay")}:</label>
                                <input
                                    type="number"
                                    name="drivingFeeDay"
                                    value={settings.drivingFeeDay}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.drivingFeeDay ? 'is-invalid' : ''}`}
                                />
                                {errors.drivingFeeDay && <div className="invalid-feedback">{errors.drivingFeeDay[0]}</div>}
                            </div>
                            <div className="form-group">
                                <label htmlFor="drivingFeeMonth">{t("admin.payslip.driving_fee_perMonth")}:</label>
                                <input
                                    type="number"
                                    name="drivingFeeMonth"
                                    value={settings.drivingFeeMonth}
                                    onChange={handleChange}
                                    required
                                    className={`form-control ${errors.drivingFeeMonth ? 'is-invalid' : ''}`}
                                />
                                {errors.drivingFeeMonth && <div className="invalid-feedback">{errors.drivingFeeMonth[0]}</div>}
                            </div>
                        </div>
                        <div className="col-md-12 text-center">
                            <button type="submit" className="btn btn-primary">{t("global.save")}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
