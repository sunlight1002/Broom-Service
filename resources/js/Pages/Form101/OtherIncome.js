import React from "react";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import CheckBox from "./inputElements/CheckBox";
import { useTranslation } from "react-i18next";

const OtherIncome = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) => {
    const { t } = useTranslation();
    const handleIncomeChange = (e) => {
        const { value, checked } = e.target;
        setFieldValue(
            "otherIncome.incomeType",
            checked
                ? [...values.otherIncome.incomeType, value]
                : values.otherIncome.incomeType.filter((type) => type !== value)
        );
    };
    return (
        <div>
            <h2>{t("form101.other_income_details")}</h2>
            <RadioButtonGroup
                name="otherIncome.haveincome"
                label={t("form101.if_other_income")}
                options={[
                    {
                        label: t("form101.if_income_no"),
                        value: "No",
                    },
                    { label: t("form101.if_income_yes"), value: "Yes" },
                ]}
                value={values.otherIncome.haveincome}
                onChange={handleChange}
                onBlur={handleBlur}
                error={
                    errors.otherIncome &&
                    touched.otherIncome &&
                    errors.otherIncome &&
                    touched.otherIncome.haveincome &&
                    errors.otherIncome.haveincome
                        ? errors.otherIncome.haveincome
                        : ""
                }
                isFlex
                required
            />
            {values.otherIncome && values.otherIncome?.haveincome === "Yes" && (
                <div className="row">
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.incomeType"}
                            label={t("form101.month_salary")}
                            value="Monthly salary"
                            checked={
                                values.otherIncome.incomeType &&
                                values.otherIncome.incomeType.includes(
                                    "Monthly salary"
                                )
                            }
                            onChange={handleIncomeChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.incomeType &&
                                errors.otherIncome.incomeType
                                    ? errors.otherIncome.incomeType
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.incomeType"}
                            label={t("form101.salary_ap")}
                            value="Salary for additional employment"
                            checked={
                                values.otherIncome.incomeType &&
                                values.otherIncome.incomeType.includes(
                                    "Salary for additional employment"
                                )
                            }
                            onChange={handleIncomeChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.incomeType &&
                                errors.otherIncome.incomeType
                                    ? errors.otherIncome.incomeType
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.incomeType"}
                            label={t("form101.partial_salary")}
                            value="Partial salary"
                            checked={
                                values.otherIncome.incomeType &&
                                values.otherIncome.incomeType.includes(
                                    "Partial salary"
                                )
                            }
                            onChange={handleIncomeChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.incomeType &&
                                errors.otherIncome.incomeType
                                    ? errors.otherIncome.incomeType
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.incomeType"}
                            label={t("form101.daily_wages")}
                            value="Wage (Daily rate of pay)"
                            checked={
                                values.otherIncome.incomeType &&
                                values.otherIncome.incomeType.includes(
                                    "Wage (Daily rate of pay)"
                                )
                            }
                            onChange={handleIncomeChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.incomeType &&
                                errors.otherIncome.incomeType
                                    ? errors.otherIncome.incomeType
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.allowance"}
                            label={t("form101.allowance")}
                            value="Allowance"
                            checked={values.otherIncome.allowance}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.allowance &&
                                errors.otherIncome.allowance
                                    ? errors.otherIncome.allowance
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-4">
                        <CheckBox
                            name={"otherIncome.scholarship"}
                            label={t("form101.scholarship")}
                            value="Scholarship"
                            checked={values.otherIncome.scholarship}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.scholarship &&
                                errors.otherIncome.scholarship
                                    ? errors.otherIncome.scholarship
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-12 mt-3">
                        <RadioButtonGroup
                            name="otherIncome.taxCreditsAtOtherIncome"
                            label={t("form101.receiveTaxCredit")}
                            options={[
                                {
                                    label: t("form101.requestReceiveTax"),
                                    value: "request",
                                },
                                {
                                    label: t("form101.receiveTax"),
                                    value: "receive",
                                },
                            ]}
                            value={values.otherIncome.taxCreditsAtOtherIncome}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.taxCreditsAtOtherIncome &&
                                errors.otherIncome.taxCreditsAtOtherIncome
                                    ? errors.otherIncome.taxCreditsAtOtherIncome
                                    : ""
                            }
                            required
                        />
                    </div>
                    <div className="col-12">
                        <CheckBox
                            name="otherIncome.studyFund"
                            label={t("form101.NoPaymentMade")}
                            value={values.otherIncome.studyFund}
                            checked={values.otherIncome.studyFund}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.studyFund &&
                                errors.otherIncome.studyFund
                                    ? errors.otherIncome.studyFund
                                    : ""
                            }
                        />
                    </div>
                    <div className="col-12">
                        <CheckBox
                            name="otherIncome.pensionInsurance"
                            label={t("form101.NoPaymentMadebehalf")}
                            value={values.otherIncome.pensionInsurance}
                            checked={values.otherIncome.pensionInsurance}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.otherIncome &&
                                errors.otherIncome &&
                                touched.otherIncome.pensionInsurance &&
                                errors.otherIncome.pensionInsurance
                                    ? errors.otherIncome.pensionInsurance
                                    : ""
                            }
                        />
                    </div>
                </div>
            )}
        </div>
    );
};

export default OtherIncome;
