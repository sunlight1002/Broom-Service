import React from "react";
import CheckBox from "./inputElements/CheckBox";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import TextField from "./inputElements/TextField";
import { useTranslation } from "react-i18next";
export const employerInitial = {
    firstName: "",
    address: "",
    fileNumber: "",
    MonthlyIncome: "",
    Tax: "",
    incomeType: "",
    payslip: null,
};
const TaxCoordination = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) => {
    const { t } = useTranslation();

    return (
        <div>
            <h2>{t("form101.taxCordination")}</h2>
            <CheckBox
                name={"TaxCoordination.hasTaxCoordination"}
                label={t("form101.taxCordination")}
                checked={
                    values.TaxCoordination &&
                    values.TaxCoordination.hasTaxCoordination
                }
                onChange={handleChange}
                onBlur={handleBlur}
                error={
                    touched.TaxCoordination &&
                    errors.TaxCoordination &&
                    touched.TaxCoordination.hasTaxCoordination &&
                    errors.TaxCoordination.hasTaxCoordination
                        ? errors.TaxCoordination.hasTaxCoordination
                        : ""
                }
            />
            {values.TaxCoordination && (
                <>
                    {values.TaxCoordination.hasTaxCoordination && (
                        <RadioButtonGroup
                            name="TaxCoordination.requestReason"
                            label={t("form101.requestReason")}
                            options={[
                                {
                                    label: t("form101.requestReason1"),
                                    value: "reason1",
                                },
                                {
                                    label: t("form101.requestReason2"),
                                    value: "reason2",
                                },
                                {
                                    label: t("form101.requestReason3"),
                                    value: "reason3",
                                },
                            ]}
                            value={values.TaxCoordination.requestReason}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                errors.TaxCoordination &&
                                touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination.requestReason &&
                                errors.TaxCoordination.requestReason
                                    ? errors.TaxCoordination.requestReason
                                    : ""
                            }
                            required
                        />
                    )}
                    {values.TaxCoordination.requestReason === "reason1" && (
                        <div>
                            <label htmlFor="TaxCoordination.requestReason1Certificate">
                                {t("form101.requestReason1Certificate")}
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxCoordination.requestReason1Certificate"
                                id="TaxCoordination.requestReason1Certificate"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxCoordination.requestReason1Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination
                                    .requestReason1Certificate &&
                                errors.TaxCoordination
                                    .requestReason1Certificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxCoordination
                                                .requestReason1Certificate
                                        }
                                    </p>
                                )}
                        </div>
                    )}
                    {values.TaxCoordination.requestReason === "reason3" && (
                        <div>
                            <label htmlFor="TaxCoordination.requestReason3Certificate">
                                {t("form101.requestReason3Certificate")}
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxCoordination.requestReason3Certificate"
                                id="TaxCoordination.requestReason3Certificate"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxCoordination.requestReason3Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxCoordination &&
                                errors.TaxCoordination &&
                                touched.TaxCoordination
                                    .requestReason3Certificate &&
                                errors.TaxCoordination
                                    .requestReason3Certificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxCoordination
                                                .requestReason3Certificate
                                        }
                                    </p>
                                )}
                        </div>
                    )}
                    {values.TaxCoordination.requestReason === "reason2" && (
                        <div>
                            {values.TaxCoordination.employer.map(
                                (child, index) => (
                                    <div key={index}>
                                        <hr />
                                        {t("form101.employerPayerSalary")}{" "}
                                        {index + 1}{" "}
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-danger"
                                            onClick={() => {
                                                const newChildren = [
                                                    ...values.TaxCoordination
                                                        .employer,
                                                ];
                                                newChildren.splice(index, 1);
                                                setFieldValue(
                                                    "TaxCoordination.employer",
                                                    newChildren
                                                );
                                            }}
                                        >
                                            -
                                        </button>
                                        <div className="row">
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].firstName`}
                                                    label={t(
                                                        "form101.label_firstName"
                                                    )}
                                                    value={child.firstName}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .firstName &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .firstName
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].address`}
                                                    label={t(
                                                        "form101.label_address"
                                                    )}
                                                    value={child.address}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .address &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .address
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].fileNumber`}
                                                    label={t(
                                                        "form101.label_ddfileId"
                                                    )}
                                                    value={child.fileNumber}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .fileNumber &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .fileNumber
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <RadioButtonGroup
                                                    name={`TaxCoordination.employer[${index}].incomeType`}
                                                    label={t(
                                                        "form101.IncomeTypes"
                                                    )}
                                                    options={[
                                                        {
                                                            label: t(
                                                                "form101.incomeTypeOption1"
                                                            ),
                                                            value: "work",
                                                        },
                                                        {
                                                            label: t(
                                                                "form101.incomeTypeOption2"
                                                            ),
                                                            value: "allowance",
                                                        },
                                                        {
                                                            label: t(
                                                                "form101.incomeTypeOption3"
                                                            ),
                                                            value: "scholarship",
                                                        },
                                                        {
                                                            label: t(
                                                                "form101.incomeTypeOption4"
                                                            ),
                                                            value: "other",
                                                        },
                                                    ]}
                                                    value={child.incomeType}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .incomeType &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .incomeType
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].MonthlyIncome`}
                                                    label={t(
                                                        "form101.MonthlyIncome"
                                                    )}
                                                    value={child.MonthlyIncome}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .MonthlyIncome &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index]
                                                            .MonthlyIncome
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="col-4">
                                                <TextField
                                                    name={`TaxCoordination.employer[${index}].Tax`}
                                                    label={t(
                                                        "form101.taxDeducted"
                                                    )}
                                                    value={child.Tax}
                                                    onChange={handleChange}
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.TaxCoordination &&
                                                        touched.TaxCoordination
                                                            .employer &&
                                                        touched.TaxCoordination
                                                            .employer[index] &&
                                                        touched.TaxCoordination
                                                            .employer[index]
                                                            .Tax &&
                                                        errors.TaxCoordination &&
                                                        errors.TaxCoordination
                                                            .employer &&
                                                        errors.TaxCoordination
                                                            .employer[index] &&
                                                        errors.TaxCoordination
                                                            .employer[index].Tax
                                                    }
                                                    required
                                                />
                                            </div>{" "}
                                            <div className="col-4">
                                                <label
                                                    htmlFor={`TaxCoordination.employer[${index}].payslip`}
                                                >
                                                    {t(
                                                        "form101.photoCopyPayslip"
                                                    )}
                                                </label>
                                                <input
                                                    type="file"
                                                    name={`TaxCoordination.employer[${index}].payslip`}
                                                    id={`TaxCoordination.employer[${index}].payslip`}
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        setFieldValue(
                                                            `TaxCoordination.employer[${index}].payslip`,
                                                            e.target.files[0]
                                                        )
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                                {touched.TaxCoordination &&
                                                    touched.TaxCoordination
                                                        .employer &&
                                                    touched.TaxCoordination
                                                        .employer[index] &&
                                                    touched.TaxCoordination
                                                        .employer[index]
                                                        .payslip &&
                                                    errors.TaxCoordination &&
                                                    errors.TaxCoordination
                                                        .employer &&
                                                    errors.TaxCoordination
                                                        .employer[index] && (
                                                        <p>
                                                            {
                                                                errors
                                                                    .TaxCoordination
                                                                    .employer[
                                                                    index
                                                                ].payslip
                                                            }
                                                        </p>
                                                    )}
                                            </div>
                                        </div>
                                    </div>
                                )
                            )}
                            <button
                                type="button"
                                className="btn btn-success save my-3"
                                onClick={() => {
                                    setFieldValue("TaxCoordination.employer", [
                                        ...values.TaxCoordination.employer,
                                        employerInitial,
                                    ]);
                                }}
                            >
                                {t("form101.addEmployerPayer")}
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default TaxCoordination;
