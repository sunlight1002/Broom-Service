import React from "react";
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
}) => {
    const { t } = useTranslation();
    const handleIncomeChange = (event) => {
        const { name, value, checked } = event.target;
        const newValue = checked ? value : "";
        setFieldValue(name, newValue);
    };
    return (
        <div>
            <h2>{t("form101.employer_income_details")}</h2>
            <div className="row">
                <div className="col-sm-4">
                    <CheckBox
                        name={"incomeType"}
                        label="Monthly salary"
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
                <div className="col-sm-4">
                    <CheckBox
                        name={"incomeType"}
                        label="Salary for additional employment"
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
                <div className="col-sm-4">
                    <CheckBox
                        name={"incomeType"}
                        label="Partial salary"
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
                <div className="col-sm-4">
                    <CheckBox
                        name={"incomeType"}
                        label="Wage (Daily rate of pay)"
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
                <div className="col-sm-4">
                    <CheckBox
                        name={"allowance"}
                        label="Allowance"
                        checked={values.allowance}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.allowance && errors.allowance
                                ? errors.allowance
                                : ""
                        }
                    />
                </div>
                <div className="col-sm-4">
                    <CheckBox
                        name={"scholarship"}
                        label="Scholarship"
                        checked={values.scholarship}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.scholarship && errors.scholarship
                                ? errors.scholarship
                                : ""
                        }
                    />
                </div>
                <div className="col-12">
                    <DateField
                        name="DateOfBeginningWork"
                        label="Date of beginning of work in the tax year"
                        value={values.DateOfBeginningWork}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.DateOfBeginningWork &&
                            errors.DateOfBeginningWork
                                ? errors.DateOfBeginningWork
                                : ""
                        }
                        required
                    />
                </div>
            </div>
        </div>
    );
};

export default IncomeDetails;
