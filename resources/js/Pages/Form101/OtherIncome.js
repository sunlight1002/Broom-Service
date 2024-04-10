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
                label="Do you have other incomes?"
                options={[
                    {
                        label: "I have no other income including scholarships",
                        value: "No",
                    },
                    { label: "I have other incomes as follows", value: "Yes" },
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
                            label="Monthly salary"
                            value="Monthly salary"
                            checked={values.otherIncome.incomeType.includes(
                                "Monthly salary"
                            )}
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
                            label="Salary for additional employment"
                            value="Salary for additional employment"
                            checked={values.otherIncome.incomeType.includes(
                                "Salary for additional employment"
                            )}
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
                            label="Partial salary"
                            value="Partial salary"
                            checked={values.otherIncome.incomeType.includes(
                                "Partial salary"
                            )}
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
                            label="Wage (Daily rate of pay)"
                            value="Wage (Daily rate of pay)"
                            checked={values.otherIncome.incomeType.includes(
                                "Wage (Daily rate of pay)"
                            )}
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
                            label="Allowance"
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
                            label="Scholarship"
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
                            label="Receives tax credits at other income"
                            options={[
                                {
                                    label: "I request to receive tax credits and tax bracket for this income (section D). I do not receive them against any other income.",
                                    value: "request",
                                },
                                {
                                    label: "I receive tax credits and tax bracket against another income, therefore I'm not entitled to them against this income",
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
                            label="No payments are made on my behalf to a Study fund from another income, or all the employer contributions made to a Study fund from another income are attached to my other income"
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
                            label="No payments are made on my behalf to pension / loss of working capacity insurance / severance pay from another income, or all the employer contributions to pension / loss of working capacity insurance / severance pay from my other income are attached to my other income."
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
