// IncomeDetails

import React, { useEffect } from "react";
import CheckBox from "./inputElements/CheckBox";
import DateField from "./inputElements/DateField";
import { useTranslation } from "react-i18next";

const IncomeDetails = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble
}) => {
    const { t } = useTranslation();
    const handleIncomeChange = (event) => {
        const { name, value, checked } = event.target;
        const newValue = checked ? value : "";
        setFieldValue(name, newValue);
    };

    // console.log(values);
    

    return (
        <div className="mt-3">
            <p className="navyblueColor font-24 font-w-500  mb-2">{t("form101.employer_income_details")}</p>

            <div className="row">
                <div className="col">
                    <DateField
                        name="DateOfBeginningWork"
                        label={t("form101.do_commencement")}
                        value={values.DateOfBeginningWork}
                        onChange={(e) => {
                            if (e.target.value !== null) {
                                setFieldValue("DateOfBeginningWork", e.target.value);
                            }
                        }}
                        toggleBubble={handleBubbleToggle}
                        onBlur={handleBlur}
                        error={
                            touched.DateOfBeginningWork && errors.DateOfBeginningWork
                                ? errors.DateOfBeginningWork
                                : ""
                        }
                        required
                    />
                    {activeBubble === 'DateOfBeginningWork' && (
                        <div className="d-flex justify-content-end">
                            <div className="speech up">
                                Info about Employee First Name!
                            </div>
                        </div>
                    )}
                </div>
            </div>
            <div className="row mt-2">
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.month_salary")}
                        value="Monthly salary"
                        checked={values.incomeType === "Monthly salary"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>{" "}
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.salary_ap")}
                        value="Salary for additional employment"
                        checked={
                            values.incomeType ===
                            "Salary for additional employment"
                        }
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.partial_salary")}
                        value="Partial salary"
                        checked={values.incomeType === "Partial salary"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
            </div>
            <div className="row">
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.daily_wages")}
                        value="Wage (Daily rate of pay)"
                        checked={
                            values.incomeType === "Wage (Daily rate of pay)"
                        }
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.allowance")}
                        value="Allowance"
                        checked={values.incomeType === "Allowance"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>
                <div className="col">
                    <CheckBox
                        name={"incomeType"}
                        label={t("form101.scholarship")}
                        value="Scholarship"
                        checked={values.incomeType === "Scholarship"}
                        onChange={handleIncomeChange}
                        onBlur={handleBlur}
                        error={
                            touched.incomeType && errors.incomeType
                                ? errors.incomeType
                                : ""
                        }
                    />
                </div>

            </div>
        </div>
    );
};

export default IncomeDetails;
